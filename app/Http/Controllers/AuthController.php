<?php

namespace App\Http\Controllers;

use App\Mail\CodigoVerificacionMail;
use App\Mail\NuevoRegistroMail;
use App\Mail\PruebaActivadaMail;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Rules\Turnstile;
use App\Support\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* ==================== REGISTRO (con OTP) ==================== */

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'cf-turnstile-response' => [new Turnstile],
        ]);

        $request->session()->put('pending_registration', [
            'name' => $data['name'],
            'organization_name' => $data['organization_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->sendOtp($request, $data['email']);

        return redirect()->route('register.verify')
            ->with('status', __('Te enviamos un código de verificación a tu correo.'));
    }

    public function showVerifyForm(Request $request)
    {
        if (! $request->session()->has('pending_registration')) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $otp = $request->session()->get('otp');
        $pending = $request->session()->get('pending_registration');

        if (! $otp || ! $pending) {
            return redirect()->route('register');
        }

        if (now()->timestamp > $otp['expires']) {
            throw ValidationException::withMessages(['code' => __('El código expiró. Solicita uno nuevo.')]);
        }

        if (($otp['attempts'] ?? 0) >= 5) {
            throw ValidationException::withMessages(['code' => __('Demasiados intentos. Solicita un código nuevo.')]);
        }

        if (! hash_equals($otp['code'], trim($request->code))) {
            $otp['attempts'] = ($otp['attempts'] ?? 0) + 1;
            $request->session()->put('otp', $otp);
            throw ValidationException::withMessages(['code' => __('Código incorrecto.')]);
        }

        $user = $this->createAccount($pending);
        $request->session()->forget(['otp', 'pending_registration']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('status', __('¡Tu prueba gratuita de 30 días está activa!'));
    }

    public function resendOtp(Request $request)
    {
        $pending = $request->session()->get('pending_registration');

        if (! $pending) {
            return redirect()->route('register');
        }

        $this->sendOtp($request, $pending['email']);

        return back()->with('status', __('Te enviamos un nuevo código.'));
    }

    private function sendOtp(Request $request, string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('otp', [
            'code' => $code,
            'expires' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);

        Mail::to($email)->send(new CodigoVerificacionMail($code));
    }

    private function createAccount(array $pending, string $environment = 'produccion', string $plan = 'gratis'): User
    {
        $organization = Organization::create([
            'name' => $pending['organization_name'],
            'plan' => $plan,
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(Organization::TRIAL_DAYS),
            'environment' => $environment,
        ]);

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password'],
            'organization_id' => $organization->id,
        ]);

        $organization->update(['owner_id' => $user->id]);

        AuditLog::record('registro', $organization);
        NuevoRegistroMail::notificar($user, $organization);
        rescue(fn () => Mail::to($user->email)->send(new PruebaActivadaMail($user, $organization)), null, false);

        return $user;
    }

    /* ==================== LOGIN ==================== */

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'cf-turnstile-response' => [new Turnstile],
        ]);

        $email = $credentials['email'];
        $ip = $request->ip();
        $throttle = new LoginThrottle;

        if ($secs = $throttle->bloqueado($email, $ip)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta de nuevo en '.ceil($secs / 60).' min.',
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt(['email' => $email, 'password' => $credentials['password']], $remember)) {
            $throttle->exito($email, $ip);
            $request->session()->regenerate();

            $user = Auth::user();
            $user->forceFill([
                'last_login_at' => now(),
                'login_count' => (int) $user->login_count + 1,
            ])->save();
            AuditLog::record('login');

            return redirect()->intended('/');
        }

        $throttle->fallo($email, $ip);

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /* ==================== INVITACIONES BETA ==================== */

    public function showInvitation(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted() || $invitation->isExpired()) {
            return redirect()->route('register')
                ->withErrors(['email' => __('Esta invitación ya no es válida.')]);
        }

        return view('auth.invitation', compact('invitation'));
    }

    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_if($invitation->isAccepted() || $invitation->isExpired(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $this->createAccount([
            'name' => $data['name'],
            'organization_name' => $data['organization_name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
        ], $invitation->environment, $invitation->plan);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('status', __('¡Bienvenido! Tu prueba de 30 días está activa.'));
    }
}
