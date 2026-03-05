<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Authentication') | SorSU Scheduling System</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #660000;
            --brand-hover: #880000;
        }

        body {
            background: linear-gradient(135deg, #660000 0%, #880000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .auth-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .auth-header {
            color: white;
            padding: 30px;
            text-align: center;
        }

        .auth-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            border-radius: 50%;
            padding: 10px;
        }

        .auth-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--brand-primary);
            font-weight: bold;
        }

        .auth-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
            color: var(--brand-primary);
            font-weight: bold;
        }

        .auth-body {
            padding: 40px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 0, 0, 0.15);
        }

        .btn-primary {
            background: var(--brand-primary);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: var(--brand-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 0, 0, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-danger {
            background-color: #fee;
            color: #c00;
        }

        .auth-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 0.875rem;
        }

        .form-check-input:checked {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }

        .form-check-input:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 0, 0, 0.15);
        }

        .btn-maroon {
            background: var(--brand-primary);
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-maroon:hover {
            background: var(--brand-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 0, 0, 0.3);
        }

        .btn-maroon:active {
            transform: translateY(0);
        }

        .btn-maroon:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
        }

        .auth-footer-links {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .auth-footer-links p {
            margin: 0;
            color: #5a5a5a;
            font-size: 0.875rem;
            margin-bottom: 8px;
        }

        .auth-footer-links a {
            color: var(--brand-primary);
            text-decoration: none;
            font-weight: 550;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .auth-footer-links a:hover {
            color: var(--brand-hover);
            text-decoration: underline;
        }

        .auth-footer-links a:focus {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        /* Responsive Grid Support */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -12px;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 12px;
        }

        /* Required Field Indicator */
        .text-danger {
            color: #dc3545;
        }

        /* Password Requirements */
        .password-requirements {
            margin-top: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #ddd;
            font-size: 0.85rem;
        }

        .password-requirements ul {
            margin: 8px 0 0 0;
            padding-left: 0;
            list-style: none;
        }

        .password-requirements li {
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .bg-success {
            background-color: #28a745 !important;
            color: white;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .text-dark {
            color: #333 !important;
        }

        .text-white {
            color: white !important;
        }

        /* Input Group Support */
        .input-group {
            display: flex;
            position: relative;
        }

        .input-group .form-control {
            flex: 1;
            border-radius: 8px 0 0 8px;
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
            border-left: 0;
            padding: 12px 16px;
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
            transition: all 0.3s;
        }

        .input-group .btn:hover {
            background: #e9ecef;
            color: var(--brand-primary);
        }

        /* Gap utility */
        .gap-2 {
            gap: 8px;
        }

        .mt-4 {
            margin-top: 24px;
        }

        /* Bootstrap Validation */
        .was-validated .form-control:invalid,
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .invalid-feedback.d-block {
            display: block !important;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .auth-container {
                max-width: 100%;
                padding: 10px;
            }

            .auth-header {
                padding: 20px !important;
            }

            .auth-header img {
                width: 60px;
                height: 60px;
            }

            .auth-body {
                padding: 24px;
            }

            .btn-maroon,
            .btn-secondary,
            .btn-primary {
                width: 100%;
                padding: 12px;
            }
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Header -->
            @hasSection('form-title')
                <div class="auth-header" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-hover) 100%); color: white; padding: 24px 30px; text-align: center;">
                    <h2 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: white;">@yield('form-title')</h2>
                </div>
            @else
                <div class="auth-header">
                    <img src="{{ asset('images/logo.png') }}" alt="SorSU Logo">
                    <h1>SorSU Scheduling System</h1>
                    <p>Sorsogon State University</p>
                </div>
            @endif

            <!-- Main Content -->
            <div class="auth-body">
                @yield('content')
            </div>

            <!-- Footer -->
            @if(!View::hasSection('form-title'))
                <div class="auth-footer">
                    &copy; {{ date('Y') }} SorSU Scheduling System. All rights reserved.
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
