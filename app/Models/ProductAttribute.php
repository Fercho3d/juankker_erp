<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'organization_id',
        'nombre',
        'tipo',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('orden');
    }

    // Scopes
    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where('nombre', 'like', "%{$term}%");
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('orden');
    }
}
