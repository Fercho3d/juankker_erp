<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CrmToken extends Command
{
    protected $signature = 'crm:token {email : Correo del usuario dueño del token}
                                      {--nombre=claude : Nombre con el que se identifica el token}
                                      {--revocar : Revoca los tokens anteriores con ese nombre}';

    protected $description = 'Genera un token de API para operar el CRM desde fuera del navegador';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$this->argument('email')}.");

            return self::FAILURE;
        }

        $nombre = $this->option('nombre');

        if ($this->option('revocar')) {
            $borrados = $user->tokens()->where('name', $nombre)->delete();
            $this->warn("Tokens revocados: {$borrados}");
        }

        $token = $user->createToken($nombre)->plainTextToken;

        $this->newLine();
        $this->info("Token para {$user->name} (organización #{$user->organization_id}):");
        $this->line($token);
        $this->newLine();
        $this->comment('Guárdalo ahora: no se vuelve a mostrar.');
        $this->line('Prueba:  curl -H "Authorization: Bearer '.$token.'" -H "Accept: application/json" '.config('app.url').'/api/crm/resumen');
        $this->newLine();

        return self::SUCCESS;
    }
}
