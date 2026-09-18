<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** El sitio que trae el DENUE vivía sólo en las notas ("Sitio: ..."); se pasa a su columna. */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('sitio_web')->nullable()->after('email');
        });

        DB::table('leads')->where('notas', 'like', '%Sitio: %')->orderBy('id')
            ->chunkById(500, function ($leads) {
                foreach ($leads as $lead) {
                    if (preg_match('/^Sitio: (\S+)/m', $lead->notas, $m)) {
                        DB::table('leads')->where('id', $lead->id)->update(['sitio_web' => mb_strtolower(mb_substr($m[1], 0, 255))]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('sitio_web');
        });
    }
};
