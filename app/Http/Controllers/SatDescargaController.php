<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\SatCredencial;
use App\Models\SatSolicitud;
use App\Services\FacturaSATParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use ZipArchive;

class SatDescargaController extends Controller
{
    public function index()
    {
        $credencial = SatCredencial::where('user_id', Auth::id())->first();

        // Auto-procesar si hay credenciales guardadas
        if ($credencial) {
            $resultado = $this->autoProcesar($credencial);
            if ($resultado['importadas'] > 0) {
                $msg = "Auto-importación: {$resultado['importadas']} facturas nuevas, {$resultado['duplicadas']} duplicadas omitidas.";

                return redirect()->route('facturas.index')->with('success', $msg);
            }
            if ($resultado['verificadas'] > 0) {
                session()->flash('info', "Se verificaron {$resultado['verificadas']} solicitud(es). El SAT aún las está procesando.");
            }
        }

        $solicitudes = SatSolicitud::where('user_id', Auth::id())->orderByDesc('created_at')->paginate(15);
        $añosDisponibles = range(now()->year, 2017);

        return view('sat-descarga.index', compact('solicitudes', 'credencial', 'añosDisponibles'));
    }

    /**
     * Auto-verifica solicitudes en_proceso y auto-descarga las que estén listas.
     */
    private function autoProcesar(SatCredencial $credencial): array
    {
        $importadas = 0;
        $duplicadas = 0;
        $verificadas = 0;

        try {
            $service = $this->buildService(
                $credencial->getCerContents(),
                $credencial->getKeyContents(),
                $credencial->getPassword()
            );
        } catch (\Throwable) {
            return compact('importadas', 'duplicadas', 'verificadas');
        }

        // Verificar solicitudes pendientes
        $pendientes = SatSolicitud::where('user_id', Auth::id())
            ->whereIn('estado', ['en_proceso', 'pendiente'])
            ->get();

        foreach ($pendientes as $sol) {
            try {
                $result = $service->verify($sol->id_solicitud);
                $status = $result->getStatusRequest();

                if ($status->isFinished()) {
                    $sol->update([
                        'estado' => 'lista',
                        'total_cfdis' => $result->getNumberCfdis(),
                        'paquetes_ids' => $result->getPackagesIds(),
                    ]);
                } elseif (! $status->isInProgress() && ! $status->isAccepted()) {
                    $sol->update(['estado' => 'rechazada', 'mensaje_error' => $status->label()]);
                }

                $verificadas++;
            } catch (\Throwable) {
                // Si falla una verificación, seguir con las demás
            }
        }

        // Descargar las que estén listas
        $listas = SatSolicitud::where('user_id', Auth::id())->where('estado', 'lista')->get();

        foreach ($listas as $sol) {
            try {
                [$imp, $dup, $errs] = $this->importarPaquetes($sol, $service);
                $importadas += $imp;
                $duplicadas += $dup;
                $sol->update([
                    'estado' => 'descargada',
                    'facturas_importadas' => $imp,
                    'facturas_duplicadas' => $dup,
                    'mensaje_error' => $errs ? implode(' | ', array_slice($errs, 0, 5)) : null,
                ]);
            } catch (\Throwable $e) {
                $sol->update(['mensaje_error' => 'Error auto-proceso: '.$e->getMessage()]);
            }
        }

        return compact('importadas', 'duplicadas', 'verificadas');
    }

    /**
     * Guarda o actualiza la e.firma del usuario.
     */
    public function guardarCredencial(Request $request)
    {
        $credencial = SatCredencial::where('user_id', Auth::id())->first();

        $rules = [
            'key_password' => 'required|string',
            'cer_file' => ($credencial ? 'nullable' : 'required').'|file|max:512',
            'key_file' => ($credencial ? 'nullable' : 'required').'|file|max:512',
        ];
        $request->validate($rules);

        try {
            // Si suben archivos nuevos los procesamos; si no, usamos los guardados
            $cerContents = $request->hasFile('cer_file')
                ? $request->file('cer_file')->get()
                : ($credencial ? $credencial->getCerContents() : null);

            $keyContents = $request->hasFile('key_file')
                ? $request->file('key_file')->get()
                : ($credencial ? $credencial->getKeyContents() : null);

            if (! $cerContents || ! $keyContents) {
                return back()->withErrors(['cer_file' => 'Debes subir el .cer y .key la primera vez.']);
            }

            // Validar que la e.firma funcione antes de guardar
            $fiel = Fiel::create($cerContents, $keyContents, $request->key_password);
            if (! $fiel->isValid()) {
                return back()->withErrors(['key_password' => 'La e.firma no es válida o la contraseña es incorrecta.']);
            }

            // Extraer RFC y vigencia del certificado
            $cert = new Certificate($cerContents);
            $rfc = $cert->rfc();
            $nombre = $cert->legalName();
            $vigencia = \Carbon\Carbon::instance($cert->validToDateTime())->toDateString();

            // Guardar archivos en disco privado
            $userId = Auth::id();
            $cerPath = "sat_efirma/{$userId}/efirma.cer";
            $keyPath = "sat_efirma/{$userId}/efirma.key";

            if ($request->hasFile('cer_file')) {
                Storage::disk('local')->put($cerPath, $cerContents);
            }
            if ($request->hasFile('key_file')) {
                Storage::disk('local')->put($keyPath, $keyContents);
            }

            SatCredencial::updateOrCreate(
                ['user_id' => $userId],
                [
                    'rfc' => $rfc,
                    'nombre' => $nombre,
                    'cer_path' => $cerPath,
                    'key_path' => $keyPath,
                    'key_password_encrypted' => Crypt::encryptString($request->key_password),
                    'vigencia' => $vigencia,
                ]
            );

            return redirect()->route('sat.index')->with('success', "e.firma guardada. RFC: {$rfc}, vigente hasta: {$vigencia}.");

        } catch (\Exception $e) {
            return back()->withErrors(['cer_file' => 'Error al guardar: '.$e->getMessage()]);
        }
    }

    /**
     * Elimina la e.firma guardada.
     */
    public function eliminarCredencial()
    {
        $credencial = SatCredencial::where('user_id', Auth::id())->first();
        if ($credencial) {
            Storage::disk('local')->delete($credencial->cer_path);
            Storage::disk('local')->delete($credencial->key_path);
            $credencial->delete();
        }

        return redirect()->route('sat.index')->with('success', 'e.firma eliminada del servidor.');
    }

    /**
     * Solicitar descarga masiva al SAT.
     * Si hay credenciales guardadas, las usa automáticamente.
     */
    public function solicitar(Request $request)
    {
        $request->validate([
            'tipo_factura' => 'required|in:emitida,recibida',
            'modo_fecha' => 'required|in:año,rango',
            'año' => 'required_if:modo_fecha,año|nullable|integer|min:2010|max:'.now()->year,
            'fecha_inicio' => 'required_if:modo_fecha,rango|nullable|date',
            'fecha_fin' => 'required_if:modo_fecha,rango|nullable|date|after_or_equal:fecha_inicio',
        ]);

        try {
            [$cerContents, $keyContents, $password] = $this->resolveCredentials($request);
            $service = $this->buildService($cerContents, $keyContents, $password);

            $downloadType = $request->tipo_factura === 'emitida'
                ? DownloadType::issued()
                : DownloadType::received();

            if ($request->modo_fecha === 'año') {
                $inicio = $request->año.'-01-01T00:00:00';
                if ((int) $request->año >= now()->year) {
                    // Para el año actual (o futuro): usar ayer 23:59:59 en hora México
                    // para garantizar que la fecha fin siempre esté en el pasado para el SAT.
                    $fin = now('America/Mexico_City')->subDay()->format('Y-m-d').'T23:59:59';
                } else {
                    $fin = $request->año.'-12-31T23:59:59';
                }
            } else {
                $inicio = $request->fecha_inicio.'T00:00:00';
                $fin = $request->fecha_fin.'T23:59:59';
            }

            $period = DateTimePeriod::createFromValues($inicio, $fin);
            $params = QueryParameters::create(
                period: $period,
                downloadType: $downloadType,
                requestType: RequestType::xml(),
            )->withDocumentStatus(DocumentStatus::active());

            $result = $service->query($params);

            if (! $result->getStatus()->isAccepted()) {
                throw new \Exception('SAT rechazó la solicitud: '.$result->getStatus()->getMessage());
            }

            // Guardar credenciales si el usuario lo pidió
            $this->maybeSaveCredentials($request, $cerContents, $keyContents, $password);

            $fechaInicio = $request->modo_fecha === 'año' ? $request->año.'-01-01' : $request->fecha_inicio;
            $fechaFin = $request->modo_fecha === 'año' ? $request->año.'-12-31' : $request->fecha_fin;

            SatSolicitud::create([
                'id_solicitud' => $result->getRequestId(),
                'tipo_factura' => $request->tipo_factura,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => 'en_proceso',
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('sat.index')
                ->with('success', 'Solicitud enviada al SAT. Regresa en unos minutos a verificar.');

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error: '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Verificar si el SAT ya preparó el paquete.
     */
    public function verificar(Request $request, SatSolicitud $solicitud)
    {
        $this->checkOwner($solicitud);

        try {
            [$cerContents, $keyContents, $password] = $this->resolveCredentials($request);
            $service = $this->buildService($cerContents, $keyContents, $password);

            $result = $service->verify($solicitud->id_solicitud);
            $statusRequest = $result->getStatusRequest();

            if ($statusRequest->isFinished()) {
                $packagesIds = $result->getPackagesIds();
                $solicitud->update([
                    'estado' => 'lista',
                    'total_cfdis' => $result->getNumberCfdis(),
                    'paquetes_ids' => $packagesIds,
                ]);

                // Auto-descarga inmediata si hay credenciales guardadas
                $credencial = SatCredencial::where('user_id', Auth::id())->first();
                if ($credencial) {
                    [$importadas, $duplicadas] = $this->importarPaquetes($solicitud, $service);
                    $solicitud->update([
                        'estado' => 'descargada',
                        'facturas_importadas' => $importadas,
                        'facturas_duplicadas' => $duplicadas,
                    ]);
                    $msg = "¡Importación completa! {$importadas} facturas nuevas, {$duplicadas} duplicadas omitidas.";

                    return redirect()->route('facturas.index')->with('success', $msg);
                }

                return redirect()->route('sat.index')
                    ->with('success', "¡Listo! {$result->getNumberCfdis()} CFDIs listos. Haz clic en 'Descargar e importar'.");
            }

            if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
                $solicitud->update(['estado' => 'en_proceso']);

                return redirect()->route('sat.index')
                    ->with('info', 'El SAT sigue procesando. Vuelve a verificar en unos minutos.');
            }

            $solicitud->update(['estado' => 'rechazada', 'mensaje_error' => $statusRequest->label()]);

            return redirect()->route('sat.index')
                ->with('error', 'Solicitud rechazada por el SAT: '.$statusRequest->label());

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error: '.$e->getMessage()]);
        }
    }

    /**
     * Re-importar una solicitud ya descargada (por si falló o salió en 0).
     */
    public function reimportar(Request $request, SatSolicitud $solicitud)
    {
        $this->checkOwner($solicitud);

        if (! in_array($solicitud->estado, ['descargada', 'lista'])) {
            return back()->with('error', 'Solo se pueden re-importar solicitudes ya descargadas o listas.');
        }

        // Si el estado es "descargada" pero tiene paquetes, regresamos a "lista" para re-descargar
        if (empty($solicitud->paquetes_ids)) {
            return back()->with('error', 'No hay paquetes guardados para re-importar. Haz una nueva solicitud.');
        }

        try {
            [$cerContents, $keyContents, $password] = $this->resolveCredentials($request);
            $service = $this->buildService($cerContents, $keyContents, $password);

            [$importadas, $duplicadas, $errores] = $this->importarPaquetes($solicitud, $service);

            $solicitud->update([
                'estado' => 'descargada',
                'facturas_importadas' => $solicitud->facturas_importadas + $importadas,
                'facturas_duplicadas' => $solicitud->facturas_duplicadas + $duplicadas,
                'mensaje_error' => $errores ? implode(' | ', array_slice($errores, 0, 5)) : null,
            ]);

            $msg = "Re-importación: {$importadas} facturas nuevas, {$duplicadas} ya existían.";
            if ($errores) {
                $msg .= ' Errores: '.implode(', ', array_slice($errores, 0, 3));
            }

            return redirect()->route('facturas.index', ['año' => $solicitud->fecha_inicio->year])
                ->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error: '.$e->getMessage()]);
        }
    }

    /**
     * Descargar paquetes ZIP e importar XMLs a facturas.
     */
    public function descargar(Request $request, SatSolicitud $solicitud)
    {
        $this->checkOwner($solicitud);

        if ($solicitud->estado !== 'lista') {
            return back()->with('error', 'Esta solicitud no está lista para descarga.');
        }

        try {
            [$cerContents, $keyContents, $password] = $this->resolveCredentials($request);
            $service = $this->buildService($cerContents, $keyContents, $password);

            [$importadas, $duplicadas, $errores] = $this->importarPaquetes($solicitud, $service);

            $solicitud->update([
                'estado' => 'descargada',
                'facturas_importadas' => $importadas,
                'facturas_duplicadas' => $duplicadas,
                'mensaje_error' => $errores ? implode(' | ', $errores) : null,
            ]);

            $msg = "Importación completa: {$importadas} facturas nuevas, {$duplicadas} duplicadas omitidas.";
            if ($errores) {
                $msg .= ' Errores: '.implode(', ', array_slice($errores, 0, 3));
            }

            return redirect()->route('facturas.index')->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error al descargar: '.$e->getMessage()]);
        }
    }

    /**
     * Descarga los paquetes ZIP del SAT e importa los XMLs a la tabla facturas.
     * Retorna [$importadas, $duplicadas, $errores].
     */
    private function importarPaquetes(SatSolicitud $solicitud, Service $service): array
    {
        $parser = new FacturaSATParser;
        $importadas = 0;
        $duplicadas = 0;
        $errores = [];

        foreach ($solicitud->paquetes_ids as $packageId) {
            $downloadResult = $service->download($packageId);

            if (! $downloadResult->getStatus()->isAccepted()) {
                $errores[] = "Paquete {$packageId}: ".$downloadResult->getStatus()->getMessage();

                continue;
            }

            $tmpZip = tempnam(sys_get_temp_dir(), 'sat_').'.zip';
            file_put_contents($tmpZip, $downloadResult->getPackageContent());

            $zip = new ZipArchive;
            if ($zip->open($tmpZip) !== true) {
                $errores[] = "No se pudo abrir el paquete {$packageId}";
                @unlink($tmpZip);

                continue;
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (! str_ends_with(strtolower($filename), '.xml')) {
                    continue;
                }

                $xmlContent = $zip->getFromIndex($i);

                try {
                    $datos = $parser->parse($xmlContent, pathinfo($filename, PATHINFO_FILENAME));
                } catch (\Exception $e) {
                    $errores[] = "{$filename}: ".$e->getMessage();

                    continue;
                }

                $xmlPath = 'facturas/xml/'.$datos['uuid'].'.xml';
                $existente = Factura::where('uuid', $datos['uuid'])->first();

                if ($existente) {
                    // Facturas importadas sin su XML (p. ej. migradas de otro sistema) lo recuperan aquí
                    if ($existente->user_id === $solicitud->user_id && ! Storage::disk('local')->exists($xmlPath)) {
                        Storage::disk('local')->put($xmlPath, $xmlContent);
                        $existente->update(['xml_path' => $xmlPath]);
                    }
                    $duplicadas++;

                    continue;
                }

                Storage::disk('local')->put($xmlPath, $xmlContent);

                Factura::create(array_merge($datos, [
                    'tipo_factura' => $solicitud->tipo_factura,
                    'es_deducible' => true,
                    'xml_path' => $xmlPath,
                    'user_id' => $solicitud->user_id,
                ]));

                $importadas++;
            }

            $zip->close();
            @unlink($tmpZip);
        }

        return [$importadas, $duplicadas, $errores];
    }

    // -------------------------------------------------------------------------

    /**
     * Resuelve las credenciales: usa las guardadas o las que vienen en el request.
     * Devuelve [$cerContents, $keyContents, $password].
     */
    private function resolveCredentials(Request $request): array
    {
        $credencial = SatCredencial::where('user_id', Auth::id())->first();

        if ($credencial) {
            // Usar las guardadas; pero si suben archivos nuevos, los combinamos
            $cerContents = $request->hasFile('cer_file')
                ? $request->file('cer_file')->get()
                : $credencial->getCerContents();

            $keyContents = $request->hasFile('key_file')
                ? $request->file('key_file')->get()
                : $credencial->getKeyContents();

            $password = $request->filled('key_password')
                ? $request->key_password
                : $credencial->getPassword();

            return [$cerContents, $keyContents, $password];
        }

        // Sin credenciales guardadas: los archivos son obligatorios
        $request->validate([
            'cer_file' => 'required|file|max:512',
            'key_file' => 'required|file|max:512',
            'key_password' => 'required|string',
        ]);

        return [
            $request->file('cer_file')->get(),
            $request->file('key_file')->get(),
            $request->key_password,
        ];
    }

    /**
     * Guarda las credenciales si el usuario marcó "guardar".
     */
    private function maybeSaveCredentials(Request $request, string $cer, string $key, string $password): void
    {
        if (! $request->boolean('guardar_credencial')) {
            return;
        }

        try {
            $cert = new Certificate($cer);
            $userId = Auth::id();
            $cerPath = "sat_efirma/{$userId}/efirma.cer";
            $keyPath = "sat_efirma/{$userId}/efirma.key";

            Storage::disk('local')->put($cerPath, $cer);
            Storage::disk('local')->put($keyPath, $key);

            SatCredencial::updateOrCreate(
                ['user_id' => $userId],
                [
                    'rfc' => $cert->rfc(),
                    'nombre' => $cert->legalName(),
                    'cer_path' => $cerPath,
                    'key_path' => $keyPath,
                    'key_password_encrypted' => Crypt::encryptString($password),
                    'vigencia' => \Carbon\Carbon::instance($cert->validToDateTime())->toDateString(),
                ]
            );
        } catch (\Throwable) {
            // No bloquear la solicitud si falla el guardado
        }
    }

    private function buildService(string $cerContents, string $keyContents, string $password): Service
    {
        $fiel = Fiel::create($cerContents, $keyContents, $password);

        if (! $fiel->isValid()) {
            throw new \Exception('La e.firma no es válida o está vencida.');
        }

        $requestBuilder = new FielRequestBuilder($fiel);
        $webClient = new GuzzleWebClient;

        return new Service($requestBuilder, $webClient);
    }

    /**
     * Elimina una solicitud del historial (no cancela en SAT).
     */
    public function eliminarSolicitud(SatSolicitud $solicitud)
    {
        $this->checkOwner($solicitud);
        $solicitud->delete();

        return redirect()->route('sat.index')->with('success', 'Solicitud eliminada del historial.');
    }

    private function checkOwner(SatSolicitud $solicitud): void
    {
        if ($solicitud->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
