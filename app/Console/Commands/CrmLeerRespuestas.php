<?php

namespace App\Console\Commands;

use App\Support\RespuestasDeProspectos;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\ClientManager;

class CrmLeerRespuestas extends Command
{
    protected $signature = 'crm:leer-respuestas {--dias=7 : Cuántos días hacia atrás revisar}';

    protected $description = 'Registra en la bitácora del CRM los correos que los prospectos mandan al buzón de la empresa';

    public function handle(): int
    {
        $buzon = config('services.crm_envio.remitente');
        $password = config('mail.mailers.prospeccion.password');
        $org = (int) config('services.crm_envio.organizacion');

        if (! $buzon || ! $password || ! $org) {
            $this->warn('Falta CRM_MAIL_USERNAME, CRM_MAIL_PASSWORD o CRM_ENVIO_ORGANIZACION.');

            return self::SUCCESS;
        }

        $cliente = (new ClientManager)->make([
            'host' => config('services.crm_envio.imap_host'),
            'port' => 993,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => $buzon,
            'password' => $password,
            'protocol' => 'imap',
        ]);
        $cliente->connect();

        $mensajes = $cliente->getFolderByPath('INBOX')->messages()
            ->since(now()->subDays((int) $this->option('dias')))
            ->leaveUnread()
            ->get();

        $registradas = $mensajes->filter(fn ($m) => RespuestasDeProspectos::registrar(
            $org,
            (string) $m->getFrom()->first()?->mail,
            (string) $m->getSubject(),
            $m->getTextBody() ?: strip_tags($m->getHTMLBody()),
            (string) ($m->getMessageId()->first() ?: Str::uuid()),
            $m->getDate()->toDate(),
        ))->count();

        $this->info("Respuestas registradas: {$registradas} de {$mensajes->count()} correos revisados.");

        return self::SUCCESS;
    }
}
