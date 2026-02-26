<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'client_id',
        'folio',
        'estatus',
        'subtotal',
        'impuesto',
        'total',
        'metodo_pago',
        'notas',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Boot method to associate with organization
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->organization_id = Auth::user()->organization_id;
                $model->user_id = Auth::id();
            }
        });

        static::addGlobalScope('organization', function ($builder) {
            if (Auth::check()) {
                $builder->where('organization_id', Auth::user()->organization_id);
            }
        });
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('estatus', 'completada');
    }

    public function scopePending($query)
    {
        return $query->where('estatus', 'pendiente');
    }

    // Helper Methods
    public function complete()
    {
        $this->estatus = 'completada';
        $this->folio = $this->generateFolio();
        $this->save();

        // Deduct stock
        foreach ($this->items as $item) {
            $item->variant->decrement('stock_actual', $item->cantidad);
        }
    }

    protected function generateFolio()
    {
        $lastSale = Sale::withoutGlobalScope('organization')
            ->where('organization_id', $this->organization_id)
            ->whereNotNull('folio')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastSale) {
            return 'V-' . date('Y') . '-00001';
        }

        $lastFolio = $lastSale->folio;
        // Extract number
        $parts = explode('-', $lastFolio);
        $number = intval(end($parts));

        return 'V-' . date('Y') . '-' . str_pad($number + 1, 5, '0', STR_PAD_LEFT);
    }
}
