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
            html, body {
                font-family: 'Inter', sans-serif;
                background: #f0f2f5;
                height: 100%;
                overflow: hidden;
            }
            
            /* Custom Pagination Styling */
            .pagination {
                --bs-pagination-padding-x: 0.75rem;
                --bs-pagination-padding-y: 0.375rem;
                --bs-pagination-margin-start: 0.5rem;
                --bs-pagination-margin-end: 0.5rem;
                --bs-pagination-font-size: 1rem;
                --bs-pagination-color: #6c757d;
                --bs-pagination-bg: #fff;
                --bs-pagination-border-width: 1px;
                --bs-pagination-border-color: #dee2e6;
                --bs-pagination-border-radius: 0.375rem;
                --bs-pagination-hover-color: #0d6efd;
                --bs-pagination-hover-bg: #e9ecef;
                --bs-pagination-hover-border-color: #dee2e6;
                --bs-pagination-focus-color: #0d6efd;
                --bs-pagination-focus-bg: #e9ecef;
                --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
                --bs-pagination-active-color: #fff;
                --bs-pagination-active-bg: #0d6efd;
                --bs-pagination-active-border-color: #0d6efd;
                --bs-pagination-disabled-color: #6c757d;
                --bs-pagination-disabled-bg: #fff;
                --bs-pagination-disabled-border-color: #dee2e6;
            }
            
            .pagination .page-link {
                position: relative;
                display: block;
                padding: var(--bs-pagination-padding-y) var(--bs-pagination-padding-x);
                color: var(--bs-pagination-color);
                text-decoration: none;
                background-color: var(--bs-pagination-bg);
                border: var(--bs-pagination-border-width) solid var(--bs-pagination-border-color);
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                border-radius: var(--bs-pagination-border-radius);
                margin: 0 2px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .pagination .page-link:hover {
                z-index: 2;
                color: var(--bs-pagination-hover-color);
                background-color: var(--bs-pagination-hover-bg);
                border-color: var(--bs-pagination-hover-border-color);
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            
            .pagination .page-item.active .page-link {
                z-index: 3;
                color: var(--bs-pagination-active-color);
                background-color: var(--bs-pagination-active-bg);
                border-color: var(--bs-pagination-active-border-color);
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            }
            
            .pagination .page-item.disabled .page-link {
                color: var(--bs-pagination-disabled-color);
                pointer-events: none;
                background-color: var(--bs-pagination-disabled-bg);
                border-color: var(--bs-pagination-disabled-border-color);
                opacity: 0.5;
            }
            .navbar-app {
                background: rgba(30, 30, 46, 0.98) !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.12);
                min-height: 79px;
            }
            .navbar-brand {
                font-weight: 700;
                font-size: 1.5rem;
            }
            .card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            }
            .btn-sync {
                border-radius: 12px;
                padding: 15px 30px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
            }
            .btn-sync:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            }
            .stats-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 15px;
            }
            .stats-card .card-body {
                padding: 2rem;
            }
            .sync-card {
                background: white;
                border-radius: 15px;
                padding: 2rem;
                margin-bottom: 2rem;
            }
            .table {
                border-radius: 10px;
                overflow: hidden;
            }
            .table thead th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                font-weight: 600;
            }
            .overflow-card { min-width: 0; max-width: 100%; }
            .badge {
                padding: 8px 12px;
                border-radius: 20px;
                font-weight: 500;
            }
            .main-with-sidebar {
                position: fixed;
                top: 79px;
                left: 260px;
                right: 0;
                width: calc(100vw - 260px);
                padding: 1.5rem 2rem 2rem;
                height: calc(100vh - 79px);
                overflow-y: auto;
                overflow-x: hidden;
                background: #f0f2f5;
                transition: left 0.25s ease, width 0.25s ease;
                box-sizing: border-box;
            }
            .main-with-sidebar .row,
            .main-with-sidebar .container-fluid,
            .main-with-sidebar [class^="col-"],
            .main-with-sidebar .card,
            .main-with-sidebar .card-body,
            .main-with-sidebar .table-responsive { min-width: 0; }
            .main-with-sidebar .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            @media (max-width: 991.98px) {
                .main-with-sidebar { left: 0; width: 100vw; padding: 1rem; height: calc(100vh - 79px); }
            }
        </style>
    </head>
    <body>
            @include('layouts.navigation')
            @include('layouts.sidebar')

            <!-- Page Content (offset for sidebar on desktop) -->
        <main class="main-with-sidebar container-fluid py-4">
                {{ $slot }}
            </main>

        <!-- jQuery -->
        <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
        
        <!-- Bootstrap JS -->
        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        
        <!-- Toastr JS -->
        <script src="{{ asset('assets/js/toastr.min.js') }}"></script>
        
        <!-- SweetAlert2 JS -->
        <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
        
        <!-- Toastr Configuration and Session Messages -->
        <script>
            // Toastr Configuration
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Handle session messages
            @if(session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if(session('error'))
                toastr.error('{{ session('error') }}');
            @endif

            @if(session('warning'))
                toastr.warning('{{ session('warning') }}');
            @endif

            @if(session('info'))
                toastr.info('{{ session('info') }}');
            @endif

            
        </script>

        <!-- Prevent Back Button After Logout -->
        <script>
            // Prevent back button access after logout
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    // Page was loaded from cache, redirect to login
                    window.location.href = '{{ route("login") }}';
                }
            });
            
            // Clear browser history on page load
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        @stack('scripts')
    </body>
</html>
