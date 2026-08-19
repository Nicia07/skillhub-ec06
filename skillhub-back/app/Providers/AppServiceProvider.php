<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MySQL 5.7 with utf8mb4 limits indexed keys to 767 bytes;
        // without this, unique string columns fail migration with "key too long".
        Schema::defaultStringLength(191);
    }
}
