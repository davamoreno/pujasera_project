<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Policies\MenuItemPolicy;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
    }
}
