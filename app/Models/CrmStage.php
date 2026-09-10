<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'nombre', 'orden', 'color', 'es_ganada', 'es_perdida',
    ];

    protected $casts = [
        'es_ganada' => 'boolean',
        'es_perdida' => 'boolean',
    ];

    /**
     * Embudo por defecto. Se siembra la primera vez que la organización abre el CRM.
     */
    public const DEFECTO = [
        ['nombre' => 'Nuevo',        'color' => 'slate',   'es_ganada' => false, 'es_perdida' => false],
        ['nombre' => 'Contactado',   'color' => 'sky',     'es_ganada' => false, 'es_perdida' => false],
        ['nombre' => 'Diagnóstico',  'color' => 'violet',  'es_ganada' => false, 'es_perdida' => false],
        ['nombre' => 'Propuesta',    'color' => 'amber',   'es_ganada' => false, 'es_perdida' => false],
        ['nombre' => 'Negociación',  'color' => 'orange',  'es_ganada' => false, 'es_perdida' => false],
        ['nombre' => 'Ganado',       'color' => 'emerald', 'es_ganada' => true,  'es_perdida' => false],
        ['nombre' => 'Perdido',      'color' => 'rose',    'es_ganada' => false, 'es_perdida' => true],
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Devuelve las etapas de la organización, sembrando el embudo por defecto si no existe.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function paraOrganizacion(int $organizationId)
    {
        $etapas = static::where('organization_id', $organizationId)->orderBy('orden')->get();

        if ($etapas->isNotEmpty()) {
            return $etapas;
        }

        foreach (self::DEFECTO as $i => $etapa) {
            static::create($etapa + ['organization_id' => $organizationId, 'orden' => $i]);
        }

        return static::where('organization_id', $organizationId)->orderBy('orden')->get();
    }

    public function esAbierta(): bool
    {
        return ! $this->es_ganada && ! $this->es_perdida;
    }
}
