<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saca a columnas propias lo que los importadores del DENUE guardaban dentro de
 * `notas`, para poder filtrar el embudo por giro, sector, tamaño y municipio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('giro')->nullable()->after('origen');
            $table->string('sector', 60)->nullable()->after('giro');
            $table->unsignedSmallInteger('personal_min')->nullable()->after('sector');
            $table->string('municipio', 120)->nullable()->after('personal_min');

            $table->index(['organization_id', 'sector']);
            $table->index(['organization_id', 'personal_min']);
            $table->index(['organization_id', 'municipio']);
        });

        DB::table('leads')->whereNotNull('notas')->orderBy('id')
            ->chunkById(500, function ($leads) {
                foreach ($leads as $lead) {
                    $campos = $this->desdeNotas($lead->notas);
                    if (array_filter($campos)) {
                        DB::table('leads')->where('id', $lead->id)->update($campos);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'sector']);
            $table->dropIndex(['organization_id', 'personal_min']);
            $table->dropIndex(['organization_id', 'municipio']);
            $table->dropColumn(['giro', 'sector', 'personal_min', 'municipio']);
        });
    }

    /** Las notas del DENUE vienen como "Giro: …\nPersonal: 6 a 10 personas\n…". */
    private function desdeNotas(string $notas): array
    {
        $linea = fn (string $etiqueta) => preg_match("/^{$etiqueta}: (.+)$/mu", $notas, $m) ? trim($m[1]) : null;

        $giro = $linea('Giro');
        $personal = $linea('Personal');
        $direccion = $linea('Dirección');

        return [
            'giro' => $giro ? mb_substr($giro, 0, 255) : null,
            'sector' => $linea('Sector') ?? $this->sectorDeGiro($giro),
            'personal_min' => $personal && preg_match('/\d+/', $personal, $m) ? (int) $m[0] : null,
            'municipio' => $direccion && str_contains($direccion, ',')
                ? mb_substr(trim(substr($direccion, strrpos($direccion, ',') + 1)), 0, 120)
                : null,
        ];
    }

    /** Para los leads viejos, que no traían la línea "Sector:". */
    private function sectorDeGiro(?string $giro): ?string
    {
        $prefijos = [
            'Comercio al por menor' => 'Comercio al por menor',
            'Comercio al por mayor' => 'Comercio al por mayor',
            'Reparación' => 'Talleres y reparación',
            'Fabricación' => 'Manufactura',
            'Elaboración' => 'Manufactura',
        ];
        foreach ($prefijos as $prefijo => $sector) {
            if ($giro && str_starts_with($giro, $prefijo)) {
                return $sector;
            }
        }

        return null;
    }
};
