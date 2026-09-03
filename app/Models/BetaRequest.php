<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetaRequest extends Model
{
    protected $fillable = [
        'nombre', 'email', 'empresa', 'telefono',
        'status', 'invited_at', 'ip', 'user_agent',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
    ];
}
