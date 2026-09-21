<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Correo preparado para un prospecto: el vendedor lo revisa, lo manda desde su
 * propio buzón y lo marca como enviado. El ERP no envía nada.
 */
class CrmBorrador extends Model
{
    protected $table = 'crm_borradores';

    protected $fillable = ['organization_id', 'lead_id', 'user_id', 'asunto', 'cuerpo', 'toque', 'message_id', 'responde_a', 'enviado_at'];

    protected $casts = ['enviado_at' => 'datetime'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /** Los que faltan por mandar, de los prospectos que este usuario puede ver. */
    public function scopePendientesPara($query, User $user)
    {
        return $query->where('organization_id', $user->organization_id)
            ->whereNull('enviado_at')
            ->whereHas('lead', fn ($q) => $q->visiblesPara($user));
    }

    public function mailto(): ?string
    {
        return $this->lead?->email
            ? 'mailto:'.strtolower($this->lead->email).'?subject='.rawurlencode($this->asunto).'&body='.rawurlencode($this->cuerpo)
            : null;
    }
}
