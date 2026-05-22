<?php

namespace App\Providers;

use App\Models\ClinicProfile;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        View::composer('*', function ($view) {
            $view->with('clinic', ClinicProfile::current());
        });
    }
}
