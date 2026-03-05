<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index(Request $request)
    {
        $semesters = Semester::query()
            ->with('academicYear:id,name')
            ->when($request->filled('academic_year_id'), function ($query) use ($request) {
                $query->where('academic_year_id', $request->integer('academic_year_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderBy('start_date')
            ->orderBy('name')
            ->get(['id', 'academic_year_id', 'name', 'start_date', 'end_date', 'status']);

        return response()->json([
            'data' => $semesters,
        ]);
    }
}
