<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'codigo_barras',
        'precio_compra',
        'precio_venta',
        'precio_mayoreo',
        'stock_actual',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_mayoreo' => 'decimal:2',
        'stock_actual' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attributes',
            'product_variant_id',
            'product_attribute_value_id'
        )->withTimestamps();
    }

    // Scopes
    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhere('codigo_barras', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($query) use ($term) {
                        $query->where('nombre', 'like', "%{$term}%");
                    });
            });
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('activo', $status);
        }
        return $query;
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_actual', '>', 0);
    }

    // Helper Methods
    public function getVariantName()
    {
        $attributeNames = $this->attributeValues->pluck('valor')->join(' / ');
        return $this->product->nombre . ($attributeNames ? " - {$attributeNames}" : '');
    }

    public function hasStock()
    {
        return $this->stock_actual > 0;
    }

    public function isLowStock()
    {
        return $this->stock_actual <= $this->product->stock_minimo;
    }
}
