@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-card">
        {{-- Header --}}
        <div class="auth-header mb-4">
            <h2 class="text-center mb-2">
                <i class="fa-solid fa-lock-open me-2"></i> Set New Password
            </h2>
            <p class="text-center text-muted small">
                Create a strong password for your account security.
            </p>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-exclamation-circle me-2"></i>
                <strong>Password reset failed:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Password Reset Form --}}
        <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
            @csrf

            {{-- Hidden Token --}}
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- Email Field (Display Only) --}}
            <div class="mb-4">
                <label for="email" class="form-label fw-500">
                    <i class="fa-solid fa-envelope me-2"></i> Email Address
                </label>
                <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
                    id="email" name="email" value="{{ old('email', request('email')) }}" 
                    placeholder="your@email.com" autocomplete="email" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- New Password Field --}}
            <div class="mb-4">
                <label for="password" class="form-label fw-500">
                    <i class="fa-solid fa-lock me-2"></i> New Password
                </label>
                <div class="input-group">
                    <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" 
                        id="password" name="password" 
                        placeholder="Minimum 8 characters" 
                        autocomplete="new-password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                
                {{-- Password Strength Indicator --}}
                <div class="password-requirements mt-3 p-3 bg-light rounded small">
                    <p class="mb-2 fw-500">Your password must contain:</p>
                    <ul class="mb-0 ps-3">
                        <li><span class="requirement-check" data-requirement="length">✓</span> At least 8 characters</li>
                        <li><span class="requirement-check" data-requirement="uppercase">✓</span> At least one uppercase letter (A-Z)</li>
                        <li><span class="requirement-check" data-requirement="lowercase">✓</span> At least one lowercase letter (a-z)</li>
                        <li><span class="requirement-check" data-requirement="number">✓</span> At least one number (0-9)</li>
                        <li><span class="requirement-check" data-requirement="special">✓</span> At least one special character (@$!%*?&)</li>
                    </ul>
                </div>
            </div>

            {{-- Confirm Password Field --}}
            <div class="mb-4">
                <label for="password_confirmation" class="form-label fw-500">
                    <i class="fa-solid fa-lock-open me-2"></i> Confirm Password
                </label>
                <div class="input-group">
                    <input type="password" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" 
                        id="password_confirmation" name="password_confirmation" 
                        placeholder="Re-enter your password" 
                        autocomplete="new-password" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div id="passwordMatch" class="mt-2 small"></div>
            </div>

            {{-- Submit Button --}}
            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg" id="resetBtn">
                    <i class="fa-solid fa-key me-2"></i> Reset Password
                </button>
            </div>

            {{-- Back to Login Link --}}
            <div class="text-center">
                <small>
                    <a href="{{ route('login') }}" class="link-secondary text-decoration-none">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
                    </a>
                </small>
            </div>
        </form>

        {{-- Security Notice --}}
        <div class="alert alert-warning alert-sm mt-4" role="alert">
            <small>
                <i class="fa-solid fa-shield me-2"></i>
                <strong>Security Notice:</strong> You will be logged out of all devices after resetting your password.
            </small>
        </div>
    </div>

    @push('scripts')
        <script>
            // Show/hide password toggles
            document.getElementById('togglePassword').addEventListener('click', function() {
                const input = document.getElementById('password');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });

            document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
                const input = document.getElementById('password_confirmation');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });

            // Real-time password validation
            const passwordInput = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');

            const checkPassword = () => {
                const password = passwordInput.value;
                
                // Check requirements
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /\d/.test(password),
                    special: /[@$!%*?&]/.test(password),
                };

                // Update UI for each requirement
                Object.entries(requirements).forEach(([key, met]) => {
                    const element = document.querySelector(`[data-requirement="${key}"]`);
                    if (met) {
                        element.classList.add('text-success');
                        element.classList.remove('text-danger');
                    } else {
                        element.classList.add('text-danger');
                        element.classList.remove('text-success');
                    }
                });

                // Check password match
                checkPasswordMatch();
            };

            const checkPasswordMatch = () => {
                const matchDiv = document.getElementById('passwordMatch');
                if (passwordConfirmation.value === '') {
                    matchDiv.innerHTML = '';
                    return;
                }
                
                if (passwordInput.value === passwordConfirmation.value) {
                    matchDiv.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i><span class="text-success">Passwords match</span>';
                } else {
                    matchDiv.innerHTML = '<i class="fa-solid fa-times text-danger me-1"></i><span class="text-danger">Passwords do not match</span>';
                }
            };

            passwordInput.addEventListener('input', checkPassword);
            passwordConfirmation.addEventListener('input', checkPasswordMatch);

            // Form submission
            document.getElementById('resetPasswordForm').addEventListener('submit', function() {
                const btn = document.getElementById('resetBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Resetting...';
            });

            // Initial validation check
            checkPassword();
        </script>
    @endpush
@endsection
