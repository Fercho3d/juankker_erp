<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Declaracion extends Model
{
    protected $table = 'declaraciones';

    protected $fillable = [
        'user_id', 'año', 'mes',
        'iva_pagado', 'isr_pagado',
        'fecha_presentacion', 'fecha_pago',
        'notas', 'omitida_sat', 'acuse_path', 'pago_path',
    ];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'fecha_pago' => 'date',
        'iva_pagado' => 'decimal:2',
        'isr_pagado' => 'decimal:2',
        'omitida_sat' => 'boolean',
    ];

    public function isPresentada(): bool
    {
        return ! is_null($this->fecha_presentacion);
    }

    public function isPagada(): bool
    {
        return ! is_null($this->fecha_pago);
    }
}
