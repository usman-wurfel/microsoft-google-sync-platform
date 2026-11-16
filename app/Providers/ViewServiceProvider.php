<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share settings data with all views
        View::composer('*', function ($view) {
            try {
                $settings = Setting::getCurrent();
                
                // Generate proper URLs for logo and favicon (direct public URLs)
                $settings->logo_url = $settings->logo_path ? asset($settings->logo_path) : null;
                $settings->favicon_url = $settings->favicon_path ? asset($settings->favicon_path) : null;
                
                $view->with('appSettings', $settings);
            } catch (\Exception $e) {
                // If settings table doesn't exist yet, use defaults
                $view->with('appSettings', (object) [
                    'platform_name' => 'User Sync Platform',
                    'logo_path' => null,
                    'logo_url' => null,
                    'favicon_path' => null,
                    'favicon_url' => null,
                    'description' => 'Professional user synchronization platform',
                    'version' => '1.0.0',
                ]);
            }
        });
    }
}
