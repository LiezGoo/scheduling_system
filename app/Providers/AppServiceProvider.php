<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use App\Models\YearLevel;
use App\Policies\DepartmentPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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
        Paginator::useBootstrapFive();

        // Register all policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register RBAC gates
        require base_path('app/Providers/RBACGatesProvider.php');

        View::share('academicYears', collect());
        View::share('semesterOptions', collect());
        View::share('yearLevelOptions', collect());
        View::share('contractTypeOptions', collect());
        View::share('userRoleOptions', collect());
        View::share('statusOptions', collect());
        View::share('departmentOptions', collect());
        View::share('programOptions', collect());
        View::share('subjectOptions', collect());
        View::share('facultyOptions', collect());
        View::share('blockSectionOptions', collect());

        if (Schema::hasTable('academic_years')) {
            View::share('academicYears', AcademicYear::orderBy('start_year', 'desc')->get());
        }

        if (Schema::hasTable('semesters')) {
            $semesterOptions = Semester::query()
                ->where('status', Semester::STATUS_ACTIVE)
                ->orderBy('name')
                ->pluck('name')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values();

            View::share('semesterOptions', $semesterOptions);
        }

        if (Schema::hasTable('year_levels')) {
            $yearLevelOptions = YearLevel::query()
                ->where('status', YearLevel::STATUS_ACTIVE)
                ->orderByRaw('CAST(COALESCE(NULLIF(code, \'\'), id) AS UNSIGNED)')
                ->get()
                ->map(function (YearLevel $yearLevel) {
                    $value = $yearLevel->code !== null && trim((string) $yearLevel->code) !== ''
                        ? trim((string) $yearLevel->code)
                        : (string) $yearLevel->id;

                    return [
                        'value' => $value,
                        'label' => $yearLevel->name,
                    ];
                })
                ->values();

            View::share('yearLevelOptions', $yearLevelOptions);
        }

        if (Schema::hasTable('users')) {
            $contractTypeOptions = User::query()
                ->whereNotNull('contract_type')
                ->distinct()
                ->pluck('contract_type')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values();

            // Define valid roles instead of pulling from database to avoid missing roles
            $userRoleOptions = collect([
                'student',
                'instructor',
                'department_head',
                'program_head',
                'admin',
            ]);

            $statusOptions = User::query()
                ->whereNotNull('status')
                ->distinct()
                ->pluck('status')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values();

            View::share('contractTypeOptions', $contractTypeOptions);
            View::share('userRoleOptions', $userRoleOptions);
            View::share('statusOptions', $statusOptions);

            $facultyOptions = User::query()
                ->whereNotIn('role', ['admin', 'student'])
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            View::share('facultyOptions', $facultyOptions);
        }

        if (Schema::hasTable('departments')) {
            View::share('departmentOptions', Department::query()->orderBy('department_name')->get());
        }

        if (Schema::hasTable('programs')) {
            View::share('programOptions', Program::query()->orderBy('program_name')->get());
        }

        if (Schema::hasTable('subjects')) {
            View::share('subjectOptions', \App\Models\Subject::query()->orderBy('subject_code')->get());
        }

        $blockSectionOptions = collect();

        if (Schema::hasTable('blocks')) {
            $blockSectionOptions = Block::query()
                ->where('status', Block::STATUS_ACTIVE)
                ->distinct()
                ->pluck('block_name')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->sort()
                ->values();
        }

        if ($blockSectionOptions->isEmpty() && Schema::hasTable('instructor_loads')) {
            $blockSectionOptions = \Illuminate\Support\Facades\DB::table('instructor_loads')
                ->whereNotNull('block_section')
                ->distinct()
                ->pluck('block_section')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->sort()
                ->values();
        }

        View::share('blockSectionOptions', $blockSectionOptions);

        // Fallback for modules that need year levels even before year_levels table has data.
        if (Schema::hasTable('program_subjects')) {
            /** @var Collection<int, array{value:string,label:string}> $fallbackYearLevels */
            $fallbackYearLevels = collect();

            if (View::shared('yearLevelOptions') instanceof Collection && View::shared('yearLevelOptions')->isNotEmpty()) {
                $fallbackYearLevels = View::shared('yearLevelOptions');
            } else {
                $fallbackYearLevels = \Illuminate\Support\Facades\DB::table('program_subjects')
                    ->whereNotNull('year_level')
                    ->distinct()
                    ->pluck('year_level')
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn ($value) => $value > 0)
                    ->sort()
                    ->values()
                    ->map(function (int $year) {
                        $suffix = in_array($year % 100, [11, 12, 13], true)
                            ? 'th'
                            : (($year % 10) === 1 ? 'st' : (($year % 10) === 2 ? 'nd' : (($year % 10) === 3 ? 'rd' : 'th')));

                        return [
                            'value' => (string) $year,
                            'label' => $year . $suffix . ' Year',
                        ];
                    });
            }

            View::share('yearLevelOptions', $fallbackYearLevels);
        }
    }
}
