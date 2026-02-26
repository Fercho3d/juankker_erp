<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'organization_id',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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
}
