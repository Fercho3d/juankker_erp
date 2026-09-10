<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Lista los textos de la interfaz que todavía no tienen traducción al inglés.
 *
 * El español es la lengua base: las llaves de __() son el texto en español y
 * lang/en.json las traduce. Lo que falta ahí se ve en español, que es un
 * respaldo honesto, pero conviene saberlo. Sólo ve literales: los valores que
 * llegan por variable (__($etapa->nombre)) hay que agregarlos a mano.
 */
class IdiomasRevisar extends Command
{
    protected $signature = 'idiomas:revisar {--sobrantes : Lista también traducciones que ya nadie usa}';

    protected $description = 'Muestra los textos de la interfaz sin traducción en lang/en.json';

    public function handle(): int
    {
        $usadas = $this->llavesUsadas();
        $traducidas = json_decode(File::get(lang_path('en.json')), true) ?? [];

        $faltan = array_diff($usadas, array_keys($traducidas));
        sort($faltan);

        foreach ($faltan as $llave) {
            $this->line("  <fg=yellow>falta</> {$llave}");
        }

        if ($this->option('sobrantes')) {
            foreach (array_diff(array_keys($traducidas), $usadas) as $llave) {
                $this->line("  <fg=gray>sin uso</> {$llave}");
            }
        }

        $this->info(sprintf('%d textos en la interfaz, %d sin traducir.', count($usadas), count($faltan)));

        return $faltan ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<string> */
    private function llavesUsadas(): array
    {
        $archivos = collect(File::allFiles(resource_path('views')))
            ->merge(File::allFiles(app_path()))
            ->reject(fn (SplFileInfo $f) => str_contains($f->getPathname(), '/emails/'));

        return $archivos
            ->flatMap(fn (SplFileInfo $f) => $this->literales($f->getContents()))
            ->unique()->values()->all();
    }

    /** @return list<string> */
    private function literales(string $codigo): array
    {
        preg_match_all("/(?:__|trans_choice)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $codigo, $m);

        return array_map(fn ($l) => str_replace(["\\'", '\\\\'], ["'", '\\'], $l), $m[1]);
    }
}
