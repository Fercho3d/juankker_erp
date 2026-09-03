<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key', 'name', 'tagline', 'price', 'precio_anual',
        'max_users', 'max_branches', 'max_products', 'max_storage_gb',
        'modules', 'features', 'orden', 'destacado', 'activo', 'visible',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'modules' => 'array',
        'features' => 'array',
        'destacado' => 'boolean',
        'activo' => 'boolean',
        'visible' => 'boolean',
    ];

    public function isFree(): bool
    {
        return $this->key === 'gratis' || (float) $this->price == 0.0;
    }
}
