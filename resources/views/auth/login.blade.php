@extends('layouts.auth')

@section('title', 'Login')

@section('content')
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
        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">
                Remember me on this device.
            </label>
        </div>

        {{-- Submit Button --}}
        <div class="d-grid">
            <button type="submit" class="btn btn-primary" id="loginBtn">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Login
            </button>
        </div>
    </form>
@endsection

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
