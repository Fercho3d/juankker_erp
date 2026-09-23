<?php

namespace App\Http\Controllers;

use App\Models\CrmBorrador;
use App\Support\EnvioDeCorreos;
use Illuminate\Support\Facades\Auth;

/**
 * Bandeja de correos preparados para prospectos.
 */
class CrmCorreoController extends Controller
{
    public function index()
    {
        return view('crm.correos', [
            'borradores' => CrmBorrador::pendientesPara(Auth::user())->with('lead')->oldest()->get(),
            'remitente' => EnvioDeCorreos::remitente(Auth::user()),
        ]);
    }

    /**
     * Lo manda el ERP al prospecto desde el buzón de la empresa, con copia al
     * mismo buzón, y lo registra igual que "Ya lo mandé".
     */
    public function enviar(CrmBorrador $borrador)
    {
        $lead = $this->autorizar($borrador)->lead;

        if (! $lead->email) {
            abort(403);
        }
        if (! EnvioDeCorreos::cupoDelDia($borrador->organization_id)) {
            return back()->withErrors(['envio' => __('Ya se mandaron los correos de hoy. Mañana hay más.')]);
        }

        return EnvioDeCorreos::mandar($borrador, strtolower($lead->email), true, Auth::user())
            ? $this->enviado($borrador)
            : back()->withErrors(['envio' => __('No se pudo enviar el correo. Intenta en un momento.')]);
    }

    /**
     * El mismo correo al buzón propio, para ver cómo llega. No toca al
     * prospecto ni su bitácora.
     */
    public function prueba(CrmBorrador $borrador)
    {
        $this->autorizar($borrador);
        $propio = EnvioDeCorreos::conBuzonPropio(Auth::user()) ? config('services.crm_envio.remitente') : Auth::user()->email;

        return EnvioDeCorreos::mandar($borrador, $propio, false, Auth::user())
            ? back()->with('status', __('Prueba enviada a :correo.', ['correo' => $propio]))
            : back()->withErrors(['envio' => __('No se pudo enviar el correo. Intenta en un momento.')]);
    }

    /**
     * Ya salió, desde el ERP o desde el correo del vendedor.
     */
    public function enviado(CrmBorrador $borrador)
    {
        $lead = $this->autorizar($borrador)->lead;
        EnvioDeCorreos::registrarEnviado($borrador, Auth::user());

        return back()->with('status', __('Correo registrado en la bitácora de :empresa.', ['empresa' => $lead->empresa ?: $lead->nombre]));
    }

    public function destroy(CrmBorrador $borrador)
    {
        $this->autorizar($borrador)->delete();

        return back()->with('status', __('Borrador descartado.'));
    }

    private function autorizar(CrmBorrador $borrador): CrmBorrador
    {
        if ($borrador->organization_id !== Auth::user()->organization_id || ! $borrador->lead?->visiblePara(Auth::user())) {
            abort(403);
        }

        return $borrador;
    }
}
