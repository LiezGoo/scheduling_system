@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    {{-- Display validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <strong>Registration Failed</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Registration Form --}}
    <form method="POST" action="{{ route('register.store') }}" id="registrationForm" novalidate>
        @csrf

        <div class="row">
            {{-- First Name Field --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="first_name" class="form-label">
                        <i class="fa-solid fa-user me-1"></i> First Name
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                        name="first_name" value="{{ old('first_name') }}" placeholder="Enter your first name"
                        autocomplete="given-name" required>
                    @error('first_name')
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            {{-- Last Name Field --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="last_name" class="form-label">
                        <i class="fa-solid fa-user me-1"></i> Last Name
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                        name="last_name" value="{{ old('last_name') }}" placeholder="Enter your last name"
                        autocomplete="family-name" required>
                    @error('last_name')
                        <div class="invalid-feedback d-block">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Email Field --}}
        <div class="mb-3">
            <label for="email" class="form-label">
                <i class="fa-solid fa-envelope me-1"></i> Email Address
                <span class="text-danger">*</span>
            </label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="example@gmail.com" autocomplete="email" required>
            <small class="d-block mt-1" style="color: #666; font-size: 0.8rem;">
                <i class="fa-solid fa-info-circle me-1"></i>Please use your Google email address to register.
            </small>
            @error('email')
                <div class="invalid-feedback d-block">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        {{-- Role Selection --}}
        <div class="mb-3">
            <label for="role" class="form-label">
                <i class="fa-solid fa-briefcase me-1"></i> Role
                <span class="text-danger">*</span>
            </label>
            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                <option value="" selected disabled>-- Select your role --</option>
                @foreach ($userRoleOptions->reject(fn($roleOption) => $roleOption === 'admin') as $roleOption)
                    <option value="{{ $roleOption }}" @selected(old('role') === $roleOption)>
                        {{ ucwords(str_replace('_', ' ', $roleOption)) }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback d-block">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        {{-- Password Field --}}
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="fa-solid fa-lock me-1"></i> Password
                <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" placeholder="Create a strong password" autocomplete="new-password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                    onclick="togglePasswordVisibility()">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            
            {{-- Password Requirements Checklist --}}
            <div class="password-requirements">
                <div style="font-weight: 500; margin-bottom: 8px; color: #333;">Password must contain:</div>
                <ul>
                    <li>
                        <span class="badge bg-light text-dark" id="req-length">
                            <i class="fa-solid fa-times"></i>
                        </span>
                        <span>At least 8 characters</span>
                    </li>
                    <li>
                        <span class="badge bg-light text-dark" id="req-uppercase">
                            <i class="fa-solid fa-times"></i>
                        </span>
                        <span>One uppercase letter (A-Z)</span>
                    </li>
                    <li>
                        <span class="badge bg-light text-dark" id="req-lowercase">
                            <i class="fa-solid fa-times"></i>
                        </span>
                        <span>One lowercase letter (a-z)</span>
                    </li>
                    <li>
                        <span class="badge bg-light text-dark" id="req-number">
                            <i class="fa-solid fa-times"></i>
                        </span>
                        <span>One number (0-9)</span>
                    </li>
                    <li>
                        <span class="badge bg-light text-dark" id="req-special">
                            <i class="fa-solid fa-times"></i>
                        </span>
                        <span>One special character (@$!%*?&)</span>
                    </li>
                </ul>
            </div>
            
            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        {{-- Confirm Password Field --}}
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">
                <i class="fa-solid fa-lock me-1"></i> Confirm Password
                <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" name="password_confirmation" placeholder="Re-enter your password"
                    autocomplete="new-password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm"
                    onclick="togglePasswordConfirmVisibility()">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        {{-- Terms & Conditions --}}
        <div class="form-check">
            <input type="checkbox" class="form-check-input @error('terms') is-invalid @enderror" id="terms" name="terms" value="1">
            <label class="form-check-label" for="terms">
                I agree to the <a href="#">Terms and Conditions</a> and
                <a href="#">Privacy Policy</a>
                <span class="text-danger">*</span>
            </label>
            @error('terms')
                <div class="invalid-feedback d-block" style="margin-top: 8px;">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        {{-- Action Buttons --}}
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary" id="registerBtn">
                <i class="fa-solid fa-user-plus me-2"></i> Create Account
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

    {{-- Login Link --}}
    <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e9ecef;">
        <p style="margin: 0; color: #5a5a5a; font-size: 0.875rem; margin-bottom: 8px;">
            Already have an account?
        </p>
        <a href="{{ route('login') }}" class="auth-login-link" aria-label="Login to your account">
            Login here
        </a>
    </div>
@endsection

@push('styles')
    <style>
        .auth-login-link {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 550;
            transition: all 0.2s ease;
            display: inline-block;
            position: relative;
        }

        .auth-login-link:hover {
            color: var(--brand-hover);
            text-decoration: underline;
        }

        .auth-login-link:focus {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
            border-radius: 2px;
        }

        /* Refined Password Requirements */
        .password-requirements {
            margin-top: 12px;
            padding: 14px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #ddd;
            font-size: 0.825rem;
        }

        .password-requirements > div:first-child {
            font-weight: 500;
            margin-bottom: 10px;
            color: #555;
            font-size: 0.85rem;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .password-requirements li {
            margin: 7px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements li span:not(.badge) {
            color: #666;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const btn = document.getElementById('togglePassword');
            
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        }

        function togglePasswordConfirmVisibility() {
            const input = document.getElementById('password_confirmation');
            const btn = document.getElementById('togglePasswordConfirm');
            
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        }

        // Real-time password validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[@$!%*?&]/.test(password);
            
            // Update badges
            updateBadge('req-length', hasLength);
            updateBadge('req-uppercase', hasUppercase);
            updateBadge('req-lowercase', hasLowercase);
            updateBadge('req-number', hasNumber);
            updateBadge('req-special', hasSpecial);
        });

        function updateBadge(id, isValid) {
            const badge = document.getElementById(id);
            if (isValid) {
                badge.className = 'badge bg-success text-white';
                badge.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else {
                badge.className = 'badge bg-light text-dark';
                badge.innerHTML = '<i class="fa-solid fa-times"></i>';
            }
        }

        // Disable button and show loading state on form submit
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            // Add Bootstrap validation class
            this.classList.add('was-validated');

            const registerBtn = document.getElementById('registerBtn');
            registerBtn.disabled = true;
            registerBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Creating account...';
        });
    </script>
@endpush
