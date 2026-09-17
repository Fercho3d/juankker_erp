<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Bandeja de correos preparados para prospectos.
 */
class CrmCorreoController extends Controller
{
    public function index()
    {
        return view('crm.correos', [
            'borradores' => CrmBorrador::pendientesPara(Auth::user())->with('lead')->oldest()->get(),
            'buzonPropio' => self::conBuzonPropio(),
        ]);
    }

    /**
     * Lo manda el ERP al prospecto desde el buzón de la empresa, con copia al
     * mismo buzón, y lo registra igual que "Ya lo mandé".
     */
    public function enviar(CrmBorrador $borrador)
    {
        $lead = $this->autorizar($borrador)->lead;
        $cupo = 'crm-envio:'.$borrador->organization_id;

        if (! $lead->email) {
            abort(403);
        }
        if (! RateLimiter::attempt($cupo, (int) config('services.crm_envio.por_dia'), fn () => true, 86400)) {
            return back()->withErrors(['envio' => __('Ya se mandaron los correos de hoy. Mañana hay más.')]);
        }

        return $this->mandar($borrador, strtolower($lead->email), true)
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
        $propio = self::conBuzonPropio() ? config('services.crm_envio.remitente') : Auth::user()->email;

        return $this->mandar($borrador, $propio, false)
            ? back()->with('status', __('Prueba enviada a :correo.', ['correo' => $propio]))
            : back()->withErrors(['envio' => __('No se pudo enviar el correo. Intenta en un momento.')]);
    }

    private function mandar(CrmBorrador $borrador, string $para, bool $conCopia): bool
    {
        $propio = self::conBuzonPropio();
        $usuario = Auth::user();
        $remitente = $propio ? config('services.crm_envio.remitente') : config('mail.from.address');
        $nombre = $propio ? config('services.crm_envio.nombre') : $usuario->name;
        $copia = $propio ? $remitente : $usuario->email;

        try {
            Mail::mailer($propio ? 'prospeccion' : config('mail.default'))->raw($borrador->cuerpo, function ($m) use ($borrador, $para, $conCopia, $remitente, $nombre, $usuario, $copia) {
                $m->from($remitente, $nombre)
                    ->replyTo($usuario->email, $usuario->name)
                    ->to($para)
                    ->subject($conCopia ? $borrador->asunto : __('[Prueba] :asunto', ['asunto' => $borrador->asunto]));
                if ($conCopia) {
                    $m->bcc($copia);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /**
     * Ya salió desde el buzón del vendedor: queda en la bitácora y se agenda
     * el seguimiento para que el prospecto no se enfríe.
     */
    public function enviado(CrmBorrador $borrador)
    {
        $lead = $this->autorizar($borrador)->lead;

        CrmActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => Auth::id(),
            'tipo' => 'email',
            'descripcion' => $borrador->asunto."\n\n".$borrador->cuerpo,
            'completada_at' => now(),
        ]);
        $lead->update(['ultimo_contacto_at' => now()] + ($lead->proxima_accion_at ? [] : [
            'proxima_accion' => 'Llamar si no contestó el correo',
            'proxima_accion_at' => now()->addWeekdays(3)->setTime(10, 0),
        ]));
        $borrador->update(['enviado_at' => now()]);

        return back()->with('status', __('Correo registrado en la bitácora de :empresa.', ['empresa' => $lead->empresa ?: $lead->nombre]));
    }

    public function destroy(CrmBorrador $borrador)
    {
        $this->autorizar($borrador)->delete();

        return back()->with('status', __('Borrador descartado.'));
    }

    /**
     * Con `CRM_MAIL_*` el correo sale del buzón de la empresa; sin eso sale por
     * el mailer del sistema, a nombre del vendedor y con respuesta a su correo.
     */
    private static function conBuzonPropio(): bool
    {
        return config('services.crm_envio.remitente') && config('mail.mailers.prospeccion.password')
            && (string) Auth::user()->organization_id === (string) config('services.crm_envio.organizacion');
    }

    private function autorizar(CrmBorrador $borrador): CrmBorrador
    {
        if ($borrador->organization_id !== Auth::user()->organization_id || ! $borrador->lead?->visiblePara(Auth::user())) {
            abort(403);
        }

        return $borrador;
    }
}
