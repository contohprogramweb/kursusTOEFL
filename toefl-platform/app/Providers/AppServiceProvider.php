<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Policies\ParentStudentLinkPolicy;

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
        // Register policies
        Gate::policy(ParentStudentLink::class, ParentStudentLinkPolicy::class);
    }
}
