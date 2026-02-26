<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    protected $fillable = [
        'product_attribute_id',
        'valor',
        'codigo_color',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // Relationships
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    // Scopes
    public function scopeStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('activo', $status);
        }
        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('orden');
    }
}
