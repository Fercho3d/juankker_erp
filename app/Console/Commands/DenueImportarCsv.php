<?php

namespace App\Console\Commands;

use App\Models\CrmStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Importa un CSV del DENUE sin necesidad de token.
 *
 * Sirve tanto para la descarga masiva por estado
 * (https://www.inegi.org.mx/app/descarga/?ti=6) como para la exportación
 * del buscador del DENUE (https://www.inegi.org.mx/app/mapa/denue/), que
 * permite filtrar por giro y zona antes de bajar el archivo.
 */
class DenueImportarCsv extends Command
{
    protected $signature = 'denue:csv
                            {archivo : Ruta del CSV descargado del DENUE}
                            {--giro= : Filtra por texto en la actividad o el nombre, p. ej. "refaccion"}
                            {--municipio= : Filtra por municipio}
                            {--min-personal=6 : Descarta negocios con menos personal que este}
                            {--solo-con-contacto : Solo los que traen teléfono o correo}
                            {--limite=200 : Máximo de registros a dar de alta}
                            {--importar : Da de alta los resultados en el CRM}
                            {--usuario= : Correo del dueño de los leads}';

    protected $description = 'Carga prospectos al CRM desde un CSV del DENUE (sin token)';

    /** Nombres posibles de cada campo, entre la descarga masiva y la exportación del buscador. */
    private const COLUMNAS = [
        'empresa' => ['nom_estab', 'nombre', 'nombre_estab', 'razon_social', 'raz_social'],
        'giro' => ['nombre_act', 'clase_actividad', 'actividad'],
        'personal' => ['per_ocu', 'estrato', 'personal_ocupado'],
        'telefono' => ['telefono', 'tel'],
        'email' => ['correoelec', 'correo_e', 'correo', 'email'],
        'web' => ['www', 'sitio_internet', 'web'],
        'municipio' => ['municipio', 'nom_mun'],
        'colonia' => ['nom_col', 'colonia'],
        'calle' => ['nom_vial', 'calle'],
        'numero' => ['numero_ext', 'num_exterior'],
        'cp' => ['codpos', 'cp', 'codigo_postal'],
    ];

    public function handle(): int
    {
        $ruta = $this->argument('archivo');

        if (! is_readable($ruta)) {
            $this->error("No puedo leer {$ruta}");

            return self::FAILURE;
        }

        $filas = $this->leer($ruta);

        if ($filas === null) {
            return self::FAILURE;
        }

        $this->info("Leídos {$filas->count()} registros del CSV.");

        $filtrados = $this->filtrar($filas)->take((int) $this->option('limite'));

        if ($filtrados->isEmpty()) {
            $this->warn('Ningún registro pasó los filtros. Afloja --min-personal, --giro o --municipio.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Negocio', 'Personal', 'Teléfono', 'Correo', 'Municipio'],
            $filtrados->take(25)->map(fn ($n) => [
                mb_strimwidth($n['empresa'], 0, 38, '…'),
                mb_strimwidth($n['personal'], 0, 18, '…'),
                $n['telefono'] ?: '—',
                mb_strimwidth($n['email'] ?: '—', 0, 26, '…'),
                mb_strimwidth($n['municipio'], 0, 18, '…'),
            ])->all()
        );

        $this->info("Pasaron los filtros: {$filtrados->count()}");

        if ($this->option('importar')) {
            $this->importar($filtrados);
        } else {
            $this->newLine();
            $this->comment('Vuelve a correrlo con --importar para darlos de alta en el CRM.');
        }

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, string>>|null
     */
    private function leer(string $ruta)
    {
        $archivo = fopen($ruta, 'r');
        $encabezado = fgetcsv($archivo);

        if (! $encabezado) {
            $this->error('El archivo viene vacío o no es un CSV.');
            fclose($archivo);

            return null;
        }

        // El DENUE entrega los archivos en Latin-1; el BOM y los acentos rompen el mapeo si no se normaliza.
        $normalizar = fn ($v) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $v)));
        $indices = array_flip(array_map($normalizar, $encabezado));

        $mapa = [];
        foreach (self::COLUMNAS as $campo => $posibles) {
            foreach ($posibles as $posible) {
                if (isset($indices[$posible])) {
                    $mapa[$campo] = $indices[$posible];
                    break;
                }
            }
        }

        if (! isset($mapa['empresa'])) {
            $this->error('No encontré la columna del nombre del negocio.');
            $this->line('Columnas del archivo: '.implode(', ', array_map($normalizar, $encabezado)));
            fclose($archivo);

            return null;
        }

        $filas = collect();

        while (($fila = fgetcsv($archivo)) !== false) {
            $tomar = function (string $campo) use ($mapa, $fila) {
                $valor = isset($mapa[$campo]) ? ($fila[$mapa[$campo]] ?? '') : '';
                $valor = trim((string) $valor);

                if ($valor === '' || ! mb_check_encoding($valor, 'UTF-8')) {
                    $valor = $valor === '' ? '' : mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
                }

                return $valor;
            };

            $empresa = $tomar('empresa');

            if ($empresa === '') {
                continue;
            }

            $personal = $tomar('personal');

            $filas->push([
                'empresa' => $empresa,
                'giro' => $tomar('giro'),
                'personal' => $personal ?: 'n/d',
                'min_personal' => (int) preg_replace('/\D.*/', '', $personal),
                'telefono' => $tomar('telefono') ?: null,
                'email' => filter_var($tomar('email'), FILTER_VALIDATE_EMAIL) ?: null,
                'web' => $tomar('web') ?: null,
                'municipio' => $tomar('municipio'),
                'direccion' => trim(implode(' ', array_filter([
                    $tomar('calle'), $tomar('numero'), $tomar('colonia'), $tomar('cp'),
                ]))),
            ]);
        }

        fclose($archivo);

        return $filas;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $filas
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function filtrar($filas)
    {
        $giro = $this->option('giro');
        $municipio = $this->option('municipio');
        $minimo = (int) $this->option('min-personal');

        return $filas
            ->when($giro, fn ($c) => $c->filter(
                fn ($n) => mb_stripos($n['giro'].' '.$n['empresa'], $giro) !== false
            ))
            ->when($municipio, fn ($c) => $c->filter(
                fn ($n) => mb_stripos($n['municipio'], $municipio) !== false
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
    private function importar($negocios): void
    {
        $usuario = $this->option('usuario')
            ? User::where('email', $this->option('usuario'))->first()
            : User::whereNotNull('organization_id')->orderBy('id')->first();

        if (! $usuario?->organization_id) {
            $this->error('No encontré un usuario con organización. Usa --usuario=correo.');

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
