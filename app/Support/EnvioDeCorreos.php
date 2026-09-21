<?php

namespace App\Support;

use App\Http\Controllers\CrmController;
use App\Models\CrmActivity;
use App\Models\CrmBorrador;
use App\Models\CrmStage;
use App\Models\User;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Throwable;

/**
 * Envío de correos a prospectos, desde la bandeja o desde el envío diario.
 */
class EnvioDeCorreos
{
    /**
     * Con `CRM_MAIL_*` el correo sale del buzón de la empresa; sin eso sale por
     * el mailer del sistema, a nombre del vendedor y con respuesta a su correo.
     */
    public static function conBuzonPropio(User $usuario): bool
    {
        return config('services.crm_envio.remitente') && config('mail.mailers.prospeccion.password')
            && (string) $usuario->organization_id === (string) config('services.crm_envio.organizacion');
    }

    /**
     * Sin buzón propio sale del correo del vendedor si es del dominio que el
     * mailer del sistema tiene verificado; si no, del remitente del sistema.
     */
    public static function remitente(User $usuario): string
    {
        $sistema = config('mail.from.address');

        return match (true) {
            self::conBuzonPropio($usuario) => config('services.crm_envio.remitente'),
            Str::after($usuario->email, '@') === Str::after($sistema, '@') => $usuario->email,
            default => $sistema,
        };
    }

    /**
     * Cada correo abre su propia conexión: el SMTP cierra las que se quedan
     * quietas entre un envío y otro ("421 Idle timeout").
     */
    public static function mailer(User $usuario): Mailer
    {
        $mailer = Mail::mailer(self::conBuzonPropio($usuario) ? 'prospeccion' : config('mail.default'));
        $transporte = $mailer->getSymfonyTransport();
        if ($transporte instanceof SmtpTransport) {
            $transporte->stop();
        }

        return $mailer;
    }

    public static function mandar(CrmBorrador $borrador, string $para, bool $conCopia, User $usuario): bool
    {
        $propio = self::conBuzonPropio($usuario);
        $remitente = self::remitente($usuario);
        $nombre = $propio ? config('services.crm_envio.nombre') : $usuario->name;

        try {
            self::mailer($usuario)->raw($borrador->cuerpo, function ($m) use ($borrador, $para, $conCopia, $remitente, $nombre, $usuario, $propio) {
                $borrador->message_id = SeguimientoDeProspectos::encabezadosDeHilo($m, $borrador, $remitente);
                $m->from($remitente, $nombre)
                    ->replyTo($propio ? $remitente : $usuario->email, $nombre)
                    ->to($para)
                    ->subject($conCopia ? $borrador->asunto : __('[Prueba] :asunto', ['asunto' => $borrador->asunto]));
                if ($conCopia) {
                    $m->bcc($propio ? $remitente : $usuario->email);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        if ($borrador->exists && $borrador->isDirty('message_id')) {
            $borrador->save();
        }

        return true;
    }

    /**
     * Ya salió: queda en la bitácora y se agenda el seguimiento para que el
     * prospecto no se enfríe.
     */
    public static function registrarEnviado(CrmBorrador $borrador, ?User $usuario): void
    {
        $lead = $borrador->lead;

        CrmActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $usuario?->id,
            'tipo' => 'email',
            'descripcion' => $borrador->asunto."\n\n".$borrador->cuerpo,
            'completada_at' => now(),
        ]);
        $lead->update(['ultimo_contacto_at' => now()] + ($lead->proxima_accion_at ? [] : [
            'proxima_accion' => 'Llamar si no contestó el correo',
            'proxima_accion_at' => now()->addWeekdays(3)->setTime(10, 0),
        ]));
        $borrador->update(['enviado_at' => now()]);

        // Del primer paso del embudo ("Nuevo") pasa al siguiente ("Contactado").
        [$primera, $siguiente] = CrmStage::paraOrganizacion($lead->organization_id)->take(2)->pad(2, null)->all();
        if ($siguiente && $lead->stage_id === $primera?->id) {
            CrmController::aplicarEtapa($lead, $siguiente, $primera->nombre);
        }
    }
}
