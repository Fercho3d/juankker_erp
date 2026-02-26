@extends('layouts.app')

@section('content')
    <div class="auth-container">
        <div class="auth-card" style="max-width: 500px;">
            <div class="auth-header">
                <h1 class="auth-title">Create an Account</h1>
                <p class="auth-subtitle">Start your journey with us today.</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- User Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                        value="{{ old('name') }}" required autofocus placeholder="John Doe">
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Organization Name -->
                <div class="form-group">
                    <label for="organization_name" class="form-label">Organization Name</label>
                    <input id="organization_name" type="text"
                        class="form-control @error('organization_name') is-invalid @enderror" name="organization_name"
                        value="{{ old('organization_name') }}" required placeholder="Acme Inc.">
                    @error('organization_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required placeholder="name@company.com">
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required placeholder="Create a password">
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password-confirm" class="form-label">Confirm Password</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required
                        placeholder="Confirm your password">
                </div>

                <button type="submit" class="btn-primary">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </div>
        </div>
    </div>
@endsection