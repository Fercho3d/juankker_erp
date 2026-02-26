<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantAttribute extends Model
{
    protected $fillable = [
        'product_variant_id',
        'product_attribute_value_id',
    ];

    // Relationships
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function attributeValue()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
