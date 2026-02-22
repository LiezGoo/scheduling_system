<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        Department::class => DepartmentPolicy::class,
        Program::class => ProgramPolicy::class,
        User::class => UserPolicy::class,
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
        // Register all policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register RBAC gates
        require base_path('app/Providers/RBACGatesProvider.php');

        View::share('academicYears', collect());

        if (Schema::hasTable('academic_years')) {
            View::share('academicYears', AcademicYear::orderBy('start_year', 'desc')->get());
        }
    }
}
