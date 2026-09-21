<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('crm:leer-respuestas')->everyTenMinutes()->withoutOverlapping();
        // De lunes a sábado: en Aguascalientes la mayoría de las empresas abre los sábados.
        $schedule->command('crm:enviar-diario')
            ->days([Schedule::MONDAY, Schedule::TUESDAY, Schedule::WEDNESDAY, Schedule::THURSDAY, Schedule::FRIDAY, Schedule::SATURDAY])
            ->at('09:30')->timezone('America/Mexico_City')->withoutOverlapping();
        // Después del envío del día: segundo y tercer correo a quien no contestó.
        $schedule->command('crm:seguimiento')
            ->days([Schedule::MONDAY, Schedule::TUESDAY, Schedule::WEDNESDAY, Schedule::THURSDAY, Schedule::FRIDAY, Schedule::SATURDAY])
            ->at('09:45')->timezone('America/Mexico_City')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
