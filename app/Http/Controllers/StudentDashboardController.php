<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Semester;
use App\Models\YearLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $visibleStatuses = ['APPROVED', 'FINALIZED'];

        $scheduleScope = Schedule::query()
            ->whereIn('status', $visibleStatuses);

        // If no published schedules exist yet, still surface available data from all schedules.
        if (!(clone $scheduleScope)->exists()) {
            $scheduleScope = Schedule::query();
        }

        $academicYearNames = (clone $scheduleScope)
            ->whereNotNull('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values();

        $academicYears = AcademicYear::query()
            ->when($academicYearNames->isNotEmpty(), fn ($query) => $query->whereIn('name', $academicYearNames))
            ->orderByDesc('start_year')
            ->get();

        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::query()
                ->orderByDesc('start_year')
                ->get();

            if ($academicYears->isEmpty()) {
                $academicYears = $academicYearNames
                    ->map(fn (string $name) => (object) ['id' => $name, 'name' => $name])
                    ->values();
            }
        }

        $semesterOptions = (clone $scheduleScope)
            ->whereNotNull('semester')
            ->distinct()
            ->pluck('semester')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values();

        if ($semesterOptions->isEmpty()) {
            $semesterOptions = Semester::query()
                ->where('status', Semester::STATUS_ACTIVE)
                ->orderBy('name')
                ->pluck('name')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->values();

            if ($semesterOptions->isEmpty()) {
                $semesterOptions = Semester::query()
                    ->orderBy('name')
                    ->pluck('name')
                    ->filter(fn ($value) => trim((string) $value) !== '')
                    ->values();
            }
        }

        $programIds = (clone $scheduleScope)
            ->whereNotNull('program_id')
            ->distinct()
            ->pluck('program_id')
            ->values();

        $programOptions = Program::query()
            ->when($programIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $programIds))
            ->orderBy('program_name')
            ->get();

        if ($programOptions->isEmpty()) {
            $programOptions = Program::query()
                ->orderBy('program_name')
                ->get();
        }

        $yearLevelOptions = (clone $scheduleScope)
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

        if ($yearLevelOptions->isEmpty()) {
            $yearLevels = YearLevel::query()
                ->where('status', YearLevel::STATUS_ACTIVE)
                ->get();

            if ($yearLevels->isEmpty()) {
                $yearLevels = YearLevel::query()
                    ->get();
            }

            $yearLevels = $yearLevels
                ->sortBy(function (YearLevel $yearLevel) {
                    $candidate = $yearLevel->code !== null && trim((string) $yearLevel->code) !== ''
                        ? trim((string) $yearLevel->code)
                        : (string) $yearLevel->id;

                    if (preg_match('/\d+/', $candidate, $matches) === 1) {
                        return (int) $matches[0];
                    }

                    return (int) $yearLevel->id;
                })
                ->values();

            $yearLevelOptions = $yearLevels
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
        }

        $blockSectionOptions = collect();

        // Primary source: admin-managed Block / Section records.
        if (Schema::hasTable('blocks')) {
            $blockSectionOptions = Block::query()
                ->where('status', Block::STATUS_ACTIVE)
                ->distinct()
                ->pluck('block_name')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->sort()
                ->values();
        }

        // Fallback source: existing generated schedules.
        if ($blockSectionOptions->isEmpty()) {
            $blockSectionOptions = (clone $scheduleScope)
                ->whereNotNull('block')
                ->distinct()
                ->pluck('block')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->sort()
                ->values();
        }

        // Legacy fallback for historical modules.
        if ($blockSectionOptions->isEmpty() && Schema::hasTable('instructor_loads')) {
            $blockSectionOptions = DB::table('instructor_loads')
                ->whereNotNull('block_section')
                ->distinct()
                ->pluck('block_section')
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->sort()
                ->values();
        }

        return view('dashboards.student', compact(
            'academicYears',
            'semesterOptions',
            'programOptions',
            'yearLevelOptions',
            'blockSectionOptions'
        ));
    }
}
