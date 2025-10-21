<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Policies\MenuItemPolicy;
use App\Models\MenuItem;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        MenuItem::class => MenuItemPolicy::class,
    ];
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
        // $this->registerPolicies();
    }
}
