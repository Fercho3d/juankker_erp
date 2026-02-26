<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'organization_id',
        'nombre',
        'descripcion',
        'parent_id',
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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
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

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }
}
