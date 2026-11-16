<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appSettings->platform_name ?? 'User Sync Platform' }}</title>
        
        @if($appSettings->favicon_path ?? false)
            <link rel="icon" type="image/x-icon" href="{{ asset($appSettings->favicon_path) }}">
        @endif

        <!-- Bootstrap CSS -->
        <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
        <!-- FontAwesome -->
        <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.css') }}">
        <!-- Google Fonts -->
        <link href="{{ asset('assets/css/google-fonts.css') }}" rel="stylesheet">
        
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="{{ asset('assets/css/toastr.min.css') }}">
        
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: rgba(30, 30, 46, 0.98);
                min-height: 100vh;
            }
            .auth-card {
                border: none;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            }
            .brand-logo {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 2rem;
                margin: 0 auto;
            }
            .form-control {
                border-radius: 12px;
                border: 2px solid #e9ecef;
                padding: 12px 16px;
                transition: all 0.3s ease;
            }
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 12px;
                padding: 12px 24px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center py-5">
            <!-- Brand Logo -->
            <div class="mb-4">
                <a href="/" class="text-decoration-none">
                    <div class="brand-logo">
                        @if($appSettings->logo_path ?? false)
                            <img src="{{ asset($appSettings->logo_path) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                        @else
                                <i class="fas fa-cloud-upload-alt"></i>
                        @endif
                    </div>
                </a>
            </div>

            <!-- Auth Card -->
            <div class="auth-card w-100" style="max-width: 400px;">
                <div class="card-body p-4">
                {{ $slot }}
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/js/toastr.min.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    </body>
</html>
