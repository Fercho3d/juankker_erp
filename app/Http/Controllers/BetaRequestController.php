<?php

namespace App\Http\Controllers;

use App\Models\BetaRequest;
use App\Rules\Turnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class BetaRequestController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: si el campo oculto viene lleno, es un bot; fingimos éxito.
        if ($request->filled('website')) {
            return back()->with('status', __('¡Gracias! Te contactaremos pronto.'));
        }

        $key = 'beta:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => __('Demasiadas solicitudes. Intenta más tarde.')]);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'empresa' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:40',
            'cf-turnstile-response' => [new Turnstile()],
        ]);

        BetaRequest::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'empresa' => $data['empresa'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'status' => 'pendiente',
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', __('¡Gracias! Revisaremos tu solicitud y te enviaremos una invitación.'));
    }
}
