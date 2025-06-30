<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGRE - @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 1px;
        }

        .auth-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }

        .auth-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .auth-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }
        
        .auth-description {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-error {
            color: #ef4444;
            font-size: 13px;
            margin-top: 5px;
        }

        .form-forgot {
            display: block;
            text-align: right;
            color: #2563eb;
            font-size: 14px;
            text-decoration: none;
            margin-top: 8px;
            transition: color 0.3s;
        }

        .form-forgot:hover {
            color: #1d4ed8;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .remember-me label {
            font-size: 14px;
            color: #1e293b;
        }

        .back-link {
            display: block;
            text-align: center;
            color: #2563eb;
            font-size: 14px;
            text-decoration: none;
            margin-top: 20px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #1d4ed8;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #1d4ed8;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b981;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #ef4444;
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
        }
    </style>
    <!-- Livewire Styles -->
    {{-- <livewire:styles /> --}}
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo">SGRE</div>
            <div class="auth-subtitle">Panneau d'administration</div>
        </div>

        <div class="auth-card">
            <h1 class="auth-title">@yield('page-title')</h1>
            
            @yield('description')

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <main wire:navigate>
                @yield('content')
            </main>
        </div>

        <div class="footer-text">
            &copy; {{ date('Y') }} SGRE. Tous droits réservés.
        </div>
    </div>

   

    
    
     {{-- @livewireScripts --}}

</body>
</html>