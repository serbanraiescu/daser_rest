<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Force public path to public_html if running on cPanel
        $this->app->bind('path.public', function() {
            $cpanelPath = base_path('../public_html');
            if (file_exists($cpanelPath)) {
                return $cpanelPath;
            }
            return base_path('public');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
