@extends('layouts.app')

@section('content')
    <div class="settings-container">
        <div class="settings-header">
            <h1 class="settings-title">{{ __('Configuración de Cuenta') }}</h1>
            <p class="settings-subtitle">{{ __('Administra tu información personal y seguridad.') }}</p>
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- PROFILE INFORMATION CARD                       --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <h2 class="card-title">{{ __('Información Personal') }}</h2>
                    <p class="card-description">{{ __('Actualiza tu nombre, correo electrónico y organización.') }}</p>
                </div>
            </div>

            @if (session('status') === 'profile-updated')
                <div class="alert alert-success">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ __('Tu información se ha actualizado correctamente.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="form-label">{{ __('Nombre Completo') }}</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                            name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                            name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="organization_name" class="form-label">{{ __('Nombre de Organización') }}</label>
                    <input id="organization_name" type="text"
                        class="form-control @error('organization_name') is-invalid @enderror"
                        name="organization_name"
                        value="{{ old('organization_name', $user->organization->name ?? '') }}" required>
                    @error('organization_name')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-save">{{ __('Guardar Cambios') }}</button>
                </div>
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- SECURITY / CHANGE PASSWORD CARD                --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div>
                    <h2 class="card-title">{{ __('Seguridad') }}</h2>
                    <p class="card-description">{{ __('Cambia tu contraseña para mantener tu cuenta segura.') }}</p>
                </div>
            </div>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ __('Tu contraseña se ha actualizado correctamente.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current_password" class="form-label">{{ __('Contraseña Actual') }}</label>
                    <input id="current_password" type="password"
                        class="form-control @error('current_password') is-invalid @enderror"
                        name="current_password" required placeholder="••••••••">
                    @error('current_password')
                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">{{ __('Nueva Contraseña') }}</label>
                        <input id="password" type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password" required placeholder="{{ __('Mínimo 8 caracteres') }}">
                        @error('password')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">{{ __('Confirmar Nueva Contraseña') }}</label>
                        <input id="password_confirmation" type="password" class="form-control"
                            name="password_confirmation" required placeholder="{{ __('Repite la nueva contraseña') }}">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-save">{{ __('Actualizar Contraseña') }}</button>
                </div>
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- DANGER ZONE — DELETE ACCOUNT                   --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <div class="settings-card settings-card-danger">
            <div class="settings-card-header">
                <div class="settings-card-icon icon-danger">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <h2 class="card-title">{{ __('Zona de Peligro') }}</h2>
                    <p class="card-description">{{ __('Eliminar tu cuenta es permanente y no se puede deshacer.') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}" id="delete-account-form">
                @csrf
                @method('DELETE')

                <div class="delete-section">
                    <div class="form-group" style="max-width: 400px;">
                        <label for="delete_password" class="form-label">{{ __('Confirma tu contraseña para eliminar') }}</label>
                        <input id="delete_password" type="password"
                            class="form-control @error('password', 'deleteAccount') is-invalid @enderror"
                            name="password" required placeholder="{{ __('Tu contraseña actual') }}">
                        @error('password')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <button type="submit" class="btn-danger"
                        onclick="return confirm('¿Estás seguro de que deseas eliminar tu cuenta? Esta acción es irreversible.')">
                        {{ __('Eliminar Cuenta') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
