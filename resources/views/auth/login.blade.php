@extends('layouts.app')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-subtitle">Please enter your details to sign in.</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required placeholder="••••••••">
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
            </div>
        </div>
    </div>
@endsection