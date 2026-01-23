@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    {{-- Page Header --}}
    <div class="forgot-password-header">
        <div class="icon-wrapper">
            <i class="fa-solid fa-key"></i>
        </div>
        <h2 class="page-title">Reset Your Password</h2>
        <p class="page-description">
            Enter your registered email address and we'll send you a secure link to reset your password.
        </p>
    </div>

    {{-- Status Message --}}
    @if (session('status'))
        <div class="alert alert-success d-flex align-items-start" role="alert">
            <i class="fa-solid fa-check-circle flex-shrink-0 me-3"></i>
            <div class="flex-grow-1">
                {{ session('status') }}
            </div>
            <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start" role="alert">
            <i class="fa-solid fa-exclamation-circle flex-shrink-0 me-3"></i>
            <div class="flex-grow-1">
                <strong class="d-block mb-2">Unable to send reset link</strong>
                <ul class="error-list mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Forgot Password Form --}}
    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm" class="forgot-password-form">
        @csrf

        {{-- Email Field --}}
        <div class="form-group-enhanced">
            <label for="email" class="form-label-enhanced">
                Email Address
            </label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <i class="fa-solid fa-envelope"></i>
                </span>
                <input type="email" class="form-control-enhanced @error('email') is-invalid @enderror" id="email"
                    name="email" value="{{ old('email') }}" placeholder="your.email@example.com" autocomplete="email"
                    required autofocus aria-describedby="emailHelp">
                @error('email')
                    <div class="invalid-feedback">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>
            <small id="emailHelp" class="form-helper-text">
                We'll send a password reset link to this email address
            </small>
        </div>

        {{-- Submit Button --}}
        <div class="form-actions">
            <button type="submit" class="btn-submit" id="sendLinkBtn">
                <span class="btn-content">
                    <i class="fa-solid fa-paper-plane me-2"></i>
                    <span>Send Reset Link</span>
                </span>
            </button>
        </div>

        {{-- Back to Login Link --}}
        <div class="back-link-wrapper">
            <a href="{{ route('login') }}" class="back-link">
                <i class="fa-solid fa-arrow-left me-2"></i>
                <span>Back to Login</span>
            </a>
        </div>
    </form>

    {{-- Help / Info Section --}}
    <div class="help-section">
        <div class="help-icon">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <div class="help-content">
            <strong class="help-title">Need help?</strong>
            <p class="help-text">
                If you don't receive the reset email within a few minutes, please check your spam folder.
                Still having trouble? <a href="#" class="help-link">Contact support</a> for assistance.
            </p>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
                const btn = document.getElementById('sendLinkBtn');
                btn.disabled = true;
                btn.classList.add('btn-loading');
                btn.innerHTML = `
                    <span class="btn-content">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span>Sending...</span>
                    </span>
                `;
            });
        </script>
    @endpush
@endsection

@push('styles')
    <style>
        /* ============================================
           FORGOT PASSWORD PAGE STYLES
           ============================================ */

        /* Page Header */
        .forgot-password-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(102, 0, 0, 0.08);
        }

        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #660000 0%, #880000 100%);
            border-radius: 16px;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 12px rgba(102, 0, 0, 0.15);
        }

        .icon-wrapper i {
            font-size: 1.75rem;
            color: white;
        }

        .page-title {
            font-size: 1.625rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }

        .page-description {
            font-size: 0.9375rem;
            color: #5a5a5a;
            line-height: 1.6;
            margin: 0;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }

        /* Alert Styles */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .alert i {
            font-size: 1.125rem;
            margin-top: 0.125rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .error-list {
            padding-left: 1.25rem;
            margin-top: 0.5rem;
        }

        .error-list li {
            margin-bottom: 0.25rem;
        }

        .error-list li:last-child {
            margin-bottom: 0;
        }

        /* Form Styles */
        .forgot-password-form {
            margin-top: 0;
        }

        .form-group-enhanced {
            margin-bottom: 2rem;
        }

        .form-label-enhanced {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #2c2c2c;
            margin-bottom: 0.625rem;
            letter-spacing: -0.01em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.0625rem;
            pointer-events: none;
            z-index: 2;
            transition: color 0.2s ease;
        }

        .form-control-enhanced {
            width: 100%;
            height: 52px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 0.9375rem;
            color: #2c2c2c;
            background-color: #fff;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control-enhanced::placeholder {
            color: #b8b8b8;
            opacity: 1;
        }

        .form-control-enhanced:hover {
            border-color: #b0b4ba;
        }

        .form-control-enhanced:focus {
            border-color: #660000;
            box-shadow: 0 0 0 3px rgba(102, 0, 0, 0.08);
            background-color: #fff;
        }

        .form-control-enhanced:focus+.input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #660000;
        }

        .form-control-enhanced.is-invalid {
            border-color: #dc3545;
            padding-right: 3rem;
        }

        .form-control-enhanced.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .invalid-feedback {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #dc3545;
            font-weight: 500;
        }

        .form-helper-text {
            display: block;
            margin-top: 0.625rem;
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
        }

        /* Button Styles */
        .form-actions {
            margin-bottom: 1.75rem;
        }

        .btn-submit {
            width: 100%;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #660000 0%, #880000 100%);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 0 2rem;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(102, 0, 0, 0.2);
            letter-spacing: 0.01em;
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #880000 0%, #aa0000 100%);
            box-shadow: 0 4px 16px rgba(102, 0, 0, 0.3);
            transform: translateY(-1px);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(102, 0, 0, 0.2);
        }

        .btn-submit:disabled,
        .btn-submit.btn-loading {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Back Link */
        .back-link-wrapper {
            text-align: center;
            padding-bottom: 1.75rem;
            margin-bottom: 1.75rem;
            border-bottom: 1px solid rgba(102, 0, 0, 0.08);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
            font-size: 0.9375rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .back-link:hover {
            color: #660000;
            background-color: rgba(102, 0, 0, 0.04);
        }

        .back-link i {
            font-size: 0.875rem;
            transition: transform 0.2s ease;
        }

        .back-link:hover i {
            transform: translateX(-2px);
        }

        /* Help Section */
        .help-section {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8eef4 100%);
            border: 1px solid #d1dce6;
            border-radius: 10px;
            padding: 1.25rem;
            margin-top: 0;
        }

        .help-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .help-icon i {
            font-size: 1.125rem;
            color: #2563eb;
        }

        .help-content {
            flex: 1;
        }

        .help-title {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 0.375rem;
        }

        .help-text {
            font-size: 0.875rem;
            color: #4b5d73;
            line-height: 1.6;
            margin: 0;
        }

        .help-link {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s ease;
        }

        .help-link:hover {
            color: #1d4ed8;
            border-bottom-color: #1d4ed8;
        }

        /* ============================================
           RESPONSIVE DESIGN
           ============================================ */

        /* Mobile Phones (up to 576px) */
        @media (max-width: 576px) {
            .forgot-password-header {
                margin-bottom: 1.5rem;
                padding-bottom: 1.25rem;
            }

            .icon-wrapper {
                width: 56px;
                height: 56px;
                margin-bottom: 1rem;
            }

            .icon-wrapper i {
                font-size: 1.5rem;
            }

            .page-title {
                font-size: 1.375rem;
                margin-bottom: 0.625rem;
            }

            .page-description {
                font-size: 0.875rem;
                max-width: 100%;
            }

            .alert {
                padding: 0.875rem 1rem;
                font-size: 0.875rem;
            }

            .alert i {
                font-size: 1rem;
            }

            .form-group-enhanced {
                margin-bottom: 1.5rem;
            }

            .form-control-enhanced,
            .btn-submit {
                height: 48px;
            }

            .form-control-enhanced {
                font-size: 0.875rem;
            }

            .btn-submit {
                font-size: 0.9375rem;
            }

            .help-section {
                padding: 1rem;
                gap: 0.875rem;
            }

            .help-icon {
                width: 32px;
                height: 32px;
            }

            .help-icon i {
                font-size: 1rem;
            }

            .help-title {
                font-size: 0.875rem;
            }

            .help-text {
                font-size: 0.8125rem;
            }
        }

        /* Tablets (577px to 768px) */
        @media (min-width: 577px) and (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }

            .form-control-enhanced,
            .btn-submit {
                height: 50px;
            }
        }

        /* Large screens (1200px+) */
        @media (min-width: 1200px) {
            .page-description {
                max-width: 85%;
            }
        }

        /* Accessibility: Reduced motion */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Accessibility: High contrast mode */
        @media (prefers-contrast: high) {
            .form-control-enhanced {
                border-width: 2px;
            }

            .btn-submit {
                border: 2px solid transparent;
            }

            .help-section {
                border-width: 2px;
            }
        }

        /* Print styles */
        @media print {

            .btn-submit,
            .back-link-wrapper,
            .help-section {
                display: none;
            }
        }
    </style>
@endpush
