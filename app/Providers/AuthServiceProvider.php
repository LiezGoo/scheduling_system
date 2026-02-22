<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Schedule;
use App\Models\Subject;
use App\Policies\SchedulePolicy;
use App\Policies\SubjectPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Schedule::class => SchedulePolicy::class,
        Subject::class => SubjectPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register policies using Gate::policy() instead of auth()->policy()
        // Example:
        // Gate::policy(User::class, UserPolicy::class);
    }
}

