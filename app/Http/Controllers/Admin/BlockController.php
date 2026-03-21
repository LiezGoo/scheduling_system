<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Program;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlockController extends Controller
{
    /**
     * Display a listing of the blocks.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $query = Block::with(['program', 'academicYear', 'semester', 'yearLevel']);

        // Apply filters
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->input('program_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->input('semester_id'));
        }

        if ($request->filled('year_level_id')) {
            $query->where('year_level_id', $request->input('year_level_id'));
        }

        // Search by block name
        if ($request->filled('q')) {
            $search = '%' . trim((string) $request->input('q')) . '%';
            $query->where('block_name', 'LIKE', $search);
        }

        $blocks = $query->orderBy('program_id')
            ->orderBy('academic_year_id')
            ->orderBy('semester_id')
            ->orderBy('year_level_id')
            ->orderBy('block_name')
            ->paginate($perPage)
            ->withQueryString();

        // Get filter options
        $programs = Program::orderBy('program_name')->get();
        $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();
        $semesters = Semester::orderBy('name')->get();
        $yearLevels = YearLevel::orderBy('name')->get();
        $statusOptions = [Block::STATUS_ACTIVE, Block::STATUS_INACTIVE];

        return view('admin.blocks.index', [
            'blocks' => $blocks,
            'programs' => $programs,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'yearLevels' => $yearLevels,
            'statusOptions' => $statusOptions,
            'filters' => [
                'program_id' => $request->input('program_id'),
                'academic_year_id' => $request->input('academic_year_id'),
                'semester_id' => $request->input('semester_id'),
                'year_level_id' => $request->input('year_level_id'),
                'q' => $request->input('q'),
            ],
        ]);
    }

    /**
     * Store a newly created block in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'block_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('blocks')->where(function ($query) use ($request) {
                    return $query->where('program_id', $request->program_id)
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('semester_id', $request->semester_id)
                        ->where('year_level_id', $request->year_level_id);
                }),
            ],
            'status' => ['required', Rule::in([Block::STATUS_ACTIVE, Block::STATUS_INACTIVE])],
        ], [
            'block_name.unique' => 'This block already exists for the selected program, academic year, semester, and year level.',
        ]);

        Block::create([
            'program_id' => $validated['program_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'semester_id' => $validated['semester_id'],
            'year_level_id' => $validated['year_level_id'],
            'block_name' => trim($validated['block_name']),
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.blocks.index')->with('success', 'Block created successfully.');
    }

    /**
     * Update the specified block in storage.
     */
    public function update(Request $request, Block $block)
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'block_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('blocks')->where(function ($query) use ($request) {
                    return $query->where('program_id', $request->program_id)
                        ->where('academic_year_id', $request->academic_year_id)
                        ->where('semester_id', $request->semester_id)
                        ->where('year_level_id', $request->year_level_id);
                })->ignore($block->id),
            ],
            'status' => ['required', Rule::in([Block::STATUS_ACTIVE, Block::STATUS_INACTIVE])],
        ], [
            'block_name.unique' => 'This block already exists for the selected program, academic year, semester, and year level.',
        ]);

        $block->update([
            'program_id' => $validated['program_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'semester_id' => $validated['semester_id'],
            'year_level_id' => $validated['year_level_id'],
            'block_name' => trim($validated['block_name']),
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.blocks.index')->with('success', 'Block updated successfully.');
    }

    /**
     * Remove the specified block from storage.
     */
    public function destroy(Block $block)
    {
        $block->delete();

        return redirect()->route('admin.blocks.index')->with('success', 'Block deleted successfully.');
    }
}
