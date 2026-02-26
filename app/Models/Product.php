<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'organization_id',
        'category_id',
        'brand_id',
        'supplier_id',
        'codigo',
        'nombre',
        'descripcion',
        'unidad_medida',
        'tipo_producto',
        'precio_compra',
        'precio_venta',
        'precio_mayoreo',
        'stock_minimo',
        'codigo_sat',
        'permite_decimales',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'permite_decimales' => 'boolean',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_mayoreo' => 'decimal:2',
        'stock_minimo' => 'integer',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Scopes
    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                    ->orWhere('codigo', 'like', "%{$term}%")
                    ->orWhere('descripcion', 'like', "%{$term}%");
            });
        }
        return $query;
    }

    public function scopeOfCategory($query, $categoryId)
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        return $query;
    }

    public function scopeOfBrand($query, $brandId)
    {
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }
        return $query;
    }

    public function scopeOfType($query, $tipo)
    {
        if ($tipo) {
            $query->where('tipo_producto', $tipo);
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

    // Helper Methods
    public function isSimple()
    {
        return $this->tipo_producto === 'simple';
    }

    public function isVariable()
    {
        return $this->tipo_producto === 'variable';
    }

    public function getStockTotal()
    {
        return $this->variants()->sum('stock_actual');
    }

    public function hasLowStock()
    {
        return $this->getStockTotal() <= $this->stock_minimo;
    }
}
