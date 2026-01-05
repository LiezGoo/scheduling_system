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
    </style>
    @stack('styles')
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Header with Logo -->
            <div class="auth-header">
                <img src="{{ asset('images/logo.png') }}" alt="SorSU Logo">
                <h1>SorSU Scheduling System</h1>
                <p>Sorsogon State University</p>
            </div>

            <!-- Main Content -->
            <div class="auth-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="auth-footer">
                &copy; {{ date('Y') }} SorSU Scheduling System. All rights reserved.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
