<?php

namespace App\Http\Controllers;

use App\Mail\SuscripcionActivadaMail;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Gestión de la suscripción del tenant.
 *
 * Activación LOCAL de planes (modo demo, sin pasarela). El cobro con Stripe
 * es una fase posterior: aquí se estructura para que el checkout externo
 * sólo tenga que llamar a activate() tras confirmar el pago.
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        $org = Auth::user()->organization;
        $plans = Plan::where('activo', true)->orderBy('orden')->get();

        return view('subscription.index', compact('org', 'plans'));
    }

    public function upgrade(string $planKey)
    {
        $plan = Plan::where('key', $planKey)->where('activo', true)->firstOrFail();

        return view('subscription.upgrade', compact('plan'));
    }

    public function confirm(Request $request, string $planKey)
    {
        $plan = Plan::where('key', $planKey)->where('activo', true)->firstOrFail();
        $org = Auth::user()->organization;

        $org->applyPlan($plan);
        $org->fill([
            'subscription_status' => $plan->isFree() ? 'free' : 'active',
            'subscription_ends_at' => $plan->isFree() ? null : now()->addMonth(),
        ])->save();

        AuditLog::record('suscripcion_activada', $org);

        if (! $plan->isFree()) {
            rescue(fn () => Mail::to($org->owner->email ?? Auth::user()->email)
                ->send(new SuscripcionActivadaMail($org, $plan)), null, false);
        }

        return redirect()->route('subscription.index')
            ->with('status', __('Plan :plan activado correctamente.', ['plan' => __($plan->name)]));
    }

    public function cancel(Request $request)
    {
        $org = Auth::user()->organization;
        $org->update(['subscription_status' => 'cancelled']);
        AuditLog::record('suscripcion_cancelada', $org);

        return back()->with('status', __('Tu suscripción se canceló. Seguirá activa hasta el fin del periodo.'));
    }
}
