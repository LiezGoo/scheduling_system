@extends('layouts.auth')

@section('title', 'Account Deactivated')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <!-- Error Header -->
            <div class="auth-header bg-danger">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h2>Account Deactivated</h2>
            </div>

            <!-- Message Body -->
            <div class="auth-body p-5">
                <div class="text-center">
                    <h4 class="text-danger mb-4">Your Account Has Been Deactivated</h4>

                    <p class="text-muted fs-6 mb-4">
                        Your user account has been deactivated by an administrator. You no longer have access to the SorSU
                        Scheduling System.
                    </p>

                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Need Help?</strong>
                        <p class="mb-0 mt-2">
                            Please contact your department administrator or the system administrator to reactivate your
                            account.
                        </p>
                    </div>

                    <hr class="my-5">

                    <!-- Action -->
                    <div class="d-grid gap-2">
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Return to Login
                        </a>
                    </div>

                    <!-- Additional Information -->
                    <div class="mt-5 p-4 bg-light rounded">
                        <small class="text-muted d-block mb-2">
                            <strong>Why am I seeing this?</strong>
                        </small>
                        <small class="text-muted">
                            This message appears when:
                            <ul class="text-start mt-2 mb-0">
                                <li>Your account was deactivated by an administrator</li>
                                <li>An existing session was invalidated due to deactivation</li>
                                <li>You do not have active system access</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-4">
            <small class="text-white-50">
                SorSU Scheduling System &copy; {{ date('Y') }}
            </small>
        </div>
    </div>

    <style>
        .auth-body {
            background: #f8f9fa;
            border-top: 4px solid #dc3545;
        }

        .bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .btn-outline-secondary:hover {
            background-color: #660000;
            border-color: #660000;
            color: white;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            border-left: 4px solid #ffc107;
        }

        small ul li {
            margin-bottom: 0.5rem;
        }
    </style>
@endsection
