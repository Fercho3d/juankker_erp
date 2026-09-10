<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Invitación para sumarse al equipo de una organización con un perfil dado. */
class TeamInvitation extends Model
{
    public const DIAS_VIGENCIA = 7;

    protected $fillable = ['organization_id', 'role_id', 'invited_by', 'email', 'token', 'expires_at', 'accepted_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function scopePendientes($query)
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function vigente(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /** Token nuevo y vigencia renovada: sirve para crear y para reenviar. */
    public function renovar(): self
    {
        $this->forceFill([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(self::DIAS_VIGENCIA),
        ]);

        return $this;
    }
}
