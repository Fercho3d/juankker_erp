<?php

namespace App\Console\Commands;

use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Arma listas de prospectos desde el DENUE del INEGI.
 *
 * El DENUE es el directorio oficial de unidades económicas de México: es público,
 * gratuito, y a diferencia de un raspado de Maps trae el estrato de personal
 * (para filtrar changarros de negocios reales) y, cuando la empresa lo reportó,
 * teléfono y correo.
 *
 * Token gratuito: https://www.inegi.org.mx/servicios/api_denue.html
 */
class DenueProspectar extends Command
{
    protected $signature = 'denue:prospectar
                            {giro : Giro o palabra clave, p. ej. "refaccionaria"}
                            {--entidad= : Clave de estado 01-32 (14 Jalisco, 09 CDMX, 19 Nuevo León)}
                            {--lat= : Latitud para búsqueda por radio}
                            {--lon= : Longitud para búsqueda por radio}
                            {--radio=5000 : Metros a la redonda (con --lat y --lon)}
                            {--limite=100 : Máximo de registros a traer}
                            {--municipio= : Filtra por municipio, p. ej. "Guadalajara"}
                            {--min-personal=6 : Descarta negocios con menos personal que este}
                            {--solo-con-contacto : Solo los que traen teléfono o correo}
                            {--importar : Da de alta los resultados en el CRM}
                            {--usuario= : Correo del dueño de los leads (por defecto, el primer usuario)}
                            {--csv= : Ruta donde guardar el resultado en CSV}
                            {--token= : Token del DENUE (o define DENUE_TOKEN en .env)}';

    protected $description = 'Busca negocios reales en el DENUE del INEGI y los carga al CRM';

    public function handle(): int
    {
        $token = $this->option('token') ?: env('DENUE_TOKEN');

        if (! $token) {
            $this->error('Falta el token del DENUE.');
            $this->line('Sácalo gratis en https://www.inegi.org.mx/servicios/api_denue.html y ponlo en .env como DENUE_TOKEN=...');

            return self::FAILURE;
        }

        $negocios = $this->consultar($token);

        if ($negocios === null) {
            return self::FAILURE;
        }

        $filtrados = $this->filtrar($negocios);

        $this->newLine();
        $this->info("Encontrados: {$negocios->count()}  ·  Después de filtros: {$filtrados->count()}");

        if ($filtrados->isEmpty()) {
            $this->warn('Nada que mostrar. Baja --min-personal o quita --solo-con-contacto.');

            return self::SUCCESS;
        }

        $this->table(
            ['Negocio', 'Personal', 'Teléfono', 'Correo', 'Municipio'],
            $filtrados->take(25)->map(fn ($n) => [
                mb_strimwidth($n['empresa'], 0, 38, '…'),
                $n['personal'],
                $n['telefono'] ?: '—',
                mb_strimwidth($n['email'] ?: '—', 0, 28, '…'),
                mb_strimwidth($n['municipio'], 0, 20, '…'),
            ])->all()
        );

        if ($filtrados->count() > 25) {
            $this->line('… y '.($filtrados->count() - 25).' más.');
        }

        if ($ruta = $this->option('csv')) {
            $this->guardarCsv($filtrados, $ruta);
        }

        if ($this->option('importar')) {
            $this->importar($filtrados);
        } else {
            $this->newLine();
            $this->comment('Vuelve a correrlo con --importar para darlos de alta en el CRM.');
        }

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>|null
     */
    private function consultar(string $token)
    {
        $giro = rawurlencode($this->argument('giro'));
        $limite = (int) $this->option('limite');
        $base = 'https://www.inegi.org.mx/app/api/denue/v1/consulta';

        if ($this->option('lat') && $this->option('lon')) {
            $url = sprintf(
                '%s/Buscar/%s/%s,%s/%d/%s',
                $base, $giro, $this->option('lat'), $this->option('lon'), (int) $this->option('radio'), $token
            );
        } else {
            $entidad = $this->option('entidad') ?: '00';
            $url = sprintf('%s/BuscarEntidad/%s/%s/1/%d/%s', $base, $giro, $entidad, $limite, $token);
        }

        $this->line('Consultando el DENUE…');

        try {
            $respuesta = Http::timeout(60)->get($url);
        } catch (\Throwable $e) {
            $this->error('No se pudo contactar al INEGI: '.$e->getMessage());

            return null;
        }

        $cuerpo = $respuesta->body();

        if (! $respuesta->successful() || ! str_starts_with(ltrim($cuerpo), '[')) {
            $this->error('El DENUE respondió: '.mb_strimwidth(trim($cuerpo), 0, 200, '…'));
            $this->line('Si dice "No Autorizado", revisa tu DENUE_TOKEN.');

            return null;
        }

        return collect($respuesta->json())
            ->take($limite)
            ->map(fn ($r) => $this->normalizar($r))
            ->filter(fn ($n) => $n['empresa'] !== '');
    }

    /**
     * El DENUE devuelve las llaves con mayúscula inicial y a veces vacías.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalizar(array $r): array
    {
        $limpiar = fn (?string $v) => trim((string) $v) === '' ? null : trim((string) $v);

        return [
            'empresa' => trim((string) ($r['Nombre'] ?? $r['Razon_social'] ?? '')),
            'giro' => $limpiar($r['Clase_actividad'] ?? null),
            'personal' => $limpiar($r['Estrato'] ?? null) ?? 'n/d',
            'min_personal' => (int) preg_replace('/\D.*/', '', (string) ($r['Estrato'] ?? '0')),
            'telefono' => $limpiar($r['Telefono'] ?? null),
            'email' => filter_var($r['Correo_e'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'web' => $limpiar($r['Sitio_internet'] ?? null),
            'municipio' => $this->municipioDe($r['Ubicacion'] ?? ''),
            'direccion' => trim(implode(' ', array_filter([
                $r['Tipo_vialidad'] ?? null, $r['Calle'] ?? null,
                $r['Num_Exterior'] ?? null, $r['Colonia'] ?? null, $r['CP'] ?? null,
            ]))),
        ];
    }


    /**
     * El DENUE no devuelve el municipio aparte: viene dentro de Ubicacion,
     * con el formato "LOCALIDAD, Municipio, ESTADO".
     */
    private function municipioDe(string $ubicacion): string
    {
        $partes = array_map('trim', explode(',', $ubicacion));

        return $partes[1] ?? trim($ubicacion);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $negocios
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function filtrar($negocios)
    {
        $minimo = (int) $this->option('min-personal');

        return $negocios
            ->when($this->option('municipio'), fn ($c) => $c->filter(
                fn ($n) => mb_stripos($n['municipio'], $this->option('municipio')) !== false
            ))
            ->filter(fn ($n) => $n['min_personal'] >= $minimo)
            ->when($this->option('solo-con-contacto'),
                fn ($c) => $c->filter(fn ($n) => $n['telefono'] || $n['email']))
            ->sortByDesc('min_personal')
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $negocios
     */
    private function guardarCsv($negocios, string $ruta): void
    {
        $archivo = fopen($ruta, 'w');
        fputcsv($archivo, ['empresa', 'giro', 'personal', 'telefono', 'correo', 'sitio', 'municipio', 'direccion']);

        foreach ($negocios as $n) {
            fputcsv($archivo, [
                $n['empresa'], $n['giro'], $n['personal'], $n['telefono'],
                $n['email'], $n['web'], $n['municipio'], $n['direccion'],
            ]);
        }

        fclose($archivo);
        $this->info("CSV guardado en {$ruta}");
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $negocios
     */
    private function importar($negocios): void
    {
        $usuario = $this->option('usuario')
            ? User::where('email', $this->option('usuario'))->first()
            : User::whereNotNull('organization_id')->orderBy('id')->first();

        if (! $usuario?->organization_id) {
            $this->error('No encontré un usuario con organización para asignarle los leads. Usa --usuario=correo.');

            return;
        }

        $orgId = $usuario->organization_id;
        $etapa = CrmStage::paraOrganizacion($orgId)->first();
        $creados = 0;
        $omitidos = 0;

        $barra = $this->output->createProgressBar($negocios->count());

        foreach ($negocios as $n) {
            $duplicado = Lead::deOrganizacion($orgId)
                ->where(function ($q) use ($n) {
                    $q->where('empresa', $n['empresa']);
                    if ($n['telefono']) {
                        $q->orWhere('telefono', $n['telefono']);
                    }
                    if ($n['email']) {
                        $q->orWhere('email', $n['email']);
                    }
                })
                ->exists();

            if ($duplicado) {
                $omitidos++;
                $barra->advance();

                continue;
            }

            Lead::create([
                'organization_id' => $orgId,
                'stage_id' => $etapa->id,
                'owner_id' => $usuario->id,
                'nombre' => $n['empresa'],
                'empresa' => $n['empresa'],
                'telefono' => $n['telefono'],
                'email' => $n['email'],
                'origen' => 'prospeccion',
                'probabilidad' => 10,
                'notas' => implode("\n", array_filter([
                    $n['giro'] ? "Giro: {$n['giro']}" : null,
                    "Personal: {$n['personal']}",
                    $n['direccion'] ? "Dirección: {$n['direccion']}, {$n['municipio']}" : null,
                    $n['web'] ? "Sitio: {$n['web']}" : null,
                    'Fuente: DENUE / INEGI',
                ])),
            ]);

            $creados++;
            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);
        $this->info("Dados de alta: {$creados}  ·  Omitidos por duplicado: {$omitidos}");
        $this->line('Míralos en '.config('app.url').'/crm/tablero');
    }
}
