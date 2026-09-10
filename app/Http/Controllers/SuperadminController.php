<?php

namespace App\Http\Controllers;

use App\Mail\InvitacionMail;
use App\Models\AuditLog;
use App\Models\BetaRequest;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SuperadminController extends Controller
{
    /* ==================== DASHBOARD / KPIs ==================== */

    public function dashboard()
    {
        $kpis = [
            'orgs' => Organization::count(),
            'orgs_activas' => Organization::where('subscription_status', 'active')->count(),
            'orgs_trial' => Organization::where('subscription_status', 'trial')->count(),
            'orgs_baja' => Organization::whereIn('subscription_status', ['expired', 'cancelled'])->count(),
            'usuarios' => User::where('is_superadmin', false)->count(),
            'ingreso_mensual' => $this->mrr(),
        ];

        $porPlan = Organization::selectRaw('plan, count(*) as total')
            ->groupBy('plan')->pluck('total', 'plan');

        $porEstado = Organization::selectRaw('subscription_status, count(*) as total')
            ->groupBy('subscription_status')->pluck('total', 'subscription_status');

        return view('superadmin.dashboard', compact('kpis', 'porPlan', 'porEstado'));
    }

    /** Ingreso mensual recurrente estimado según planes activos. */
    private function mrr(): float
    {
        $precios = Plan::pluck('price', 'key');

        return Organization::where('subscription_status', 'active')->get()
            ->sum(fn ($o) => (float) ($precios[$o->plan] ?? 0));
    }

    /** Ventas de una org sin el global scope de tenant. */
    private function salesFor(int $orgId)
    {
        return \App\Models\Sale::withoutGlobalScopes()->where('organization_id', $orgId);
    }

    /* ==================== ORGANIZACIONES ==================== */

    public function index(Request $request)
    {
        $orgs = Organization::query()
            ->withCount(['users', 'products'])
            ->when($request->q, fn ($qq) => $qq->where('name', 'like', "%{$request->q}%"))
            ->when($request->status, fn ($qq) => $qq->where('subscription_status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.index', compact('orgs'));
    }

    public function show(Organization $organization)
    {
        $usuarios = $organization->users()
            ->select('id', 'name', 'email', 'last_login_at', 'login_count')
            ->orderByDesc('login_count')->get();

        $stats = [
            'usuarios' => $organization->userCount(),
            'productos' => $organization->productCount(),
            'ventas' => $this->salesFor($organization->id)->count(),
            'ingresos' => $this->salesFor($organization->id)->sum('total'),
        ];

        $actividad = AuditLog::where('organization_id', $organization->id)
            ->with('user')->latest()->limit(20)->get();

        return view('superadmin.show', compact('organization', 'usuarios', 'stats', 'actividad'));
    }

    /* ==================== ESTADÍSTICAS DE USO ==================== */

    public function uso()
    {
        $orgs = Organization::query()
            ->withCount(['users', 'products'])
            ->orderByDesc('created_at')->get()
            ->map(function ($o) {
                $o->ventas_count = $this->salesFor($o->id)->count();
                $o->ingresos = $this->salesFor($o->id)->sum('total');
                $o->ultima_actividad = AuditLog::where('organization_id', $o->id)->max('created_at');

                return $o;
            });

        $usuarios = User::where('is_superadmin', false)
            ->with('organization:id,name')
            ->orderByDesc('last_login_at')->limit(50)->get();

        return view('superadmin.uso', compact('orgs', 'usuarios'));
    }

    /* ==================== SUSCRIPCIÓN DE UNA ORG ==================== */

    public function updateSubscription(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'plan' => 'required|string|exists:plans,key',
            'subscription_status' => 'required|in:trial,active,expired,cancelled,free',
            'trial_ends_at' => 'nullable|date',
        ]);

        $plan = Plan::where('key', $data['plan'])->firstOrFail();
        $organization->applyPlan($plan);
        $organization->fill([
            'subscription_status' => $data['subscription_status'],
            'trial_ends_at' => $data['trial_ends_at'] ?? $organization->trial_ends_at,
        ])->save();

        return back()->with('status', __('Suscripción actualizada.'));
    }

    public function activateTrial(Organization $organization)
    {
        $organization->update([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(Organization::TRIAL_DAYS),
            'is_active' => true,
        ]);

        return back()->with('status', __('Prueba de 30 días activada.'));
    }

    /* ==================== IMPERSONACIÓN ==================== */

    public function impersonate(Request $request, User $user)
    {
        $request->session()->put('impersonator_id', Auth::id());
        Auth::login($user);

        return redirect('/')->with('status', __('Ahora ves el ERP como :nombre.', ['nombre' => $user->name]));
    }

    public function stopImpersonating(Request $request)
    {
        $id = $request->session()->pull('impersonator_id');

        if ($id && ($admin = User::find($id))) {
            Auth::login($admin);
        }

        return redirect()->route('superadmin.index');
    }

    /* ==================== PLANES ==================== */

    public function planes()
    {
        $plans = Plan::withTrashed()->orderBy('orden')->get();

        return view('superadmin.planes', [
            'plans' => $plans,
            'showPlans' => Setting::bool('show_plans', true),
            'showPrices' => Setting::bool('show_prices', true),
        ]);
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['modules'] = $this->parseList($request->input('modules'));
        $data['features'] = $this->parseList($request->input('features'));
        Plan::create($data);

        return back()->with('status', __('Plan creado.'));
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request, $plan);
        $data['modules'] = $this->parseList($request->input('modules'));
        $data['features'] = $this->parseList($request->input('features'));
        $plan->update($data);

        if ($request->boolean('apply_existing')) {
            Organization::where('plan', $plan->key)->each(function ($o) use ($plan) {
                $o->applyPlan($plan);
                $o->save();
            });
        }

        return back()->with('status', __('Plan actualizado.'));
    }

    public function destroyPlan(Plan $plan)
    {
        $plan->delete();

        return back()->with('status', __('Plan desactivado.'));
    }

    public function updateSettings(Request $request)
    {
        Setting::put('show_plans', $request->boolean('show_plans') ? '1' : '0');
        Setting::put('show_prices', $request->boolean('show_prices') ? '1' : '0');

        return back()->with('status', __('Visibilidad actualizada.'));
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        $unique = $plan ? ",{$plan->id}" : '';

        return $request->validate([
            'key' => "required|string|max:40|unique:plans,key{$unique}",
            'name' => 'required|string|max:80',
            'tagline' => 'nullable|string|max:120',
            'price' => 'required|numeric|min:0',
            'precio_anual' => 'nullable|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:1',
            'max_storage_gb' => 'required|integer|min:1',
            'orden' => 'required|integer|min:0',
            'destacado' => 'boolean',
            'activo' => 'boolean',
            'visible' => 'boolean',
        ]);
    }

    private function parseList(?string $raw): array
    {
        return collect(explode("\n", (string) $raw))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
    }

    /* ==================== INVITACIONES ==================== */

    public function invitations()
    {
        $invitations = Invitation::latest()->paginate(20);
        $plans = Plan::where('activo', true)->orderBy('orden')->get();

        return view('superadmin.invitations', compact('invitations', 'plans'));
    }

    public function storeInvitation(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'organization_name' => 'nullable|string|max:255',
            'plan' => 'required|string|exists:plans,key',
            'environment' => 'required|in:beta,produccion',
        ]);

        $invitation = Invitation::create([
            ...$data,
            'token' => Str::random(48),
            'invited_by' => Auth::id(),
            'sent_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        rescue(fn () => Mail::to($invitation->email)->send(new InvitacionMail($invitation)), null, false);

        return back()->with('status', __('Invitación enviada a :email.', ['email' => $invitation->email]));
    }

    public function resendInvitation(Invitation $invitation)
    {
        $invitation->update(['sent_at' => now(), 'expires_at' => now()->addDays(14)]);
        rescue(fn () => Mail::to($invitation->email)->send(new InvitacionMail($invitation)), null, false);

        return back()->with('status', __('Invitación reenviada.'));
    }

    public function destroyInvitation(Invitation $invitation)
    {
        $invitation->delete();

        return back()->with('status', __('Invitación eliminada.'));
    }

    /* ==================== SOLICITUDES BETA ==================== */

    public function betaRequests()
    {
        $requests = BetaRequest::latest()->paginate(20);

        return view('superadmin.beta', compact('requests'));
    }

    public function inviteBeta(BetaRequest $betaRequest)
    {
        $invitation = Invitation::create([
            'email' => $betaRequest->email,
            'organization_name' => $betaRequest->empresa,
            'plan' => 'gratis',
            'environment' => 'beta',
            'token' => Str::random(48),
            'invited_by' => Auth::id(),
            'sent_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        rescue(fn () => Mail::to($invitation->email)->send(new InvitacionMail($invitation)), null, false);
        $betaRequest->update(['status' => 'invitado', 'invited_at' => now()]);

        return back()->with('status', __('Invitación enviada a :email.', ['email' => $betaRequest->email]));
    }

    public function dismissBeta(BetaRequest $betaRequest)
    {
        $betaRequest->update(['status' => 'descartado']);

        return back()->with('status', __('Solicitud descartada.'));
    }
}
