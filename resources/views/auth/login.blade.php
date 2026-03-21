@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    @if (session('registration_pending'))
        <div class="alert alert-warning">
            {{ session('registration_pending') }}
        </div>
    @endif

    {{-- Display validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Login Form --}}
    <form method="POST" action="{{ url('/login') }}" id="loginForm">
        @csrf

        {{-- Email Field --}}
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="fa-solid fa-envelope me-1"></i> Email Address
            </label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="Enter your email" autocomplete="off" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password Field --}}
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="fa-solid fa-lock me-1"></i> Password
            </label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Enter your password" autocomplete="off" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me Checkbox --}}
        <div class="mb-2 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">
                Remember me on this device.
            </label>
        </div>

        {{-- Forgot Password Link --}}
        <div class="mb-4 text-end">
            <a href="{{ route('password.request') }}" class="link-secondary text-decoration-none small">
                <i class="fa-solid fa-question-circle me-1"></i> Forgot Password?
            </a>
        </div>

        {{-- Submit Button --}}
        <div class="d-grid">
            <button type="submit" class="btn btn-primary" id="loginBtn">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Login
            </button>
        </div>
    </form>

    {{-- Divider --}}
    <div style="display: flex; align-items: center; margin: 24px 0; gap: 12px;">
        <div style="flex: 1; height: 1px; background: #e9ecef;"></div>
        <span style="color: #999; font-size: 0.875rem; font-weight: 500;">OR</span>
        <div style="flex: 1; height: 1px; background: #e9ecef;"></div>
    </div>

    {{-- Google Sign-In Button --}}
    <div class="d-grid">
        <a href="{{ route('google.login') }}" class="btn btn-light border" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 500; border-radius: 6px; padding: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <span>Continue with Google</span>
        </a>
    </div>

    {{-- Registration Link --}}
    <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e9ecef;">
        <p style="margin: 0; color: #5a5a5a; font-size: 0.875rem; margin-bottom: 8px;">
            Don't have an account?
        </p>
        <a href="{{ route('register') }}" class="auth-register-link" aria-label="Create a new account">
            Create one here
        </a>
    </div>
@endsection

@push('styles')
    <style>
        .auth-register-link {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 550;
            transition: all 0.2s ease;
            display: inline-block;
            position: relative;
        }

        .auth-register-link:hover {
            color: var(--brand-hover);
            text-decoration: underline;
        }

        .auth-register-link:focus {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
            border-radius: 2px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Disable button and show loading state on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.disabled = true;
            loginBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Logging in...';
        });
    </script>
@endpush
