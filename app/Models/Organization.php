<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    const TRIAL_DAYS = 30;

    protected $fillable = [
        'name', 'owner_id', 'plan', 'subscription_status',
        'trial_ends_at', 'subscription_ends_at',
        'max_users', 'max_branches', 'max_products', 'max_storage_gb',
        'is_active', 'environment', 'stripe_id',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function planModel()
    {
        return $this->belongsTo(Plan::class, 'plan', 'key');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /* -------------------- Estado de suscripción -------------------- */

    public function esPremium(): bool
    {
        return $this->plan !== 'gratis';
    }

    public function inTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) ceil(now()->floatDiffInDays($this->trial_ends_at, false)));
    }

    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->subscription_status === 'free') {
            return true;
        }

        if ($this->subscription_status === 'active') {
            return ! $this->subscription_ends_at || $this->subscription_ends_at->isFuture();
        }

        return $this->inTrial();
    }

    public function allowsModule(string $key): bool
    {
        $modules = $this->planModel?->modules;

        if (empty($modules)) {
            return ! $this->plan || $this->plan === 'gratis'
                ? in_array($key, ['clientes', 'productos', 'pos', 'ventas'], true)
                : true;
        }

        return in_array($key, $modules, true);
    }

    /* -------------------- Límites por plan -------------------- */

    /** Miembros activos a los que se les puede asignar un prospecto. */
    public function vendedores(): \Illuminate\Support\Collection
    {
        return $this->users()->activos()->where('is_superadmin', false)->with('role', 'organization')
            ->orderBy('name')->get()->filter(fn (User $u) => $u->puede('crm'))->values();
    }

    public function userCount(): int
    {
        return $this->users()->where('is_superadmin', false)->count();
    }

    public function canAddUser(): bool
    {
        return $this->userCount() < $this->max_users;
    }

    public function productCount(): int
    {
        return $this->products()->count();
    }

    public function canAddProduct(): bool
    {
        return $this->productCount() < $this->max_products;
    }

    /**
     * Aplica los límites de un plan a esta organización.
     */
    public function applyPlan(Plan $plan): void
    {
        $this->fill([
            'plan' => $plan->key,
            'max_users' => $plan->max_users,
            'max_branches' => $plan->max_branches,
            'max_products' => $plan->max_products,
            'max_storage_gb' => $plan->max_storage_gb,
        ]);
    }
}
