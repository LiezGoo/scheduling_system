<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ProgramController extends Controller
{
    /**
     * Display a listing of programs with optional filters.
     * Note: This is UI scaffolding, ready for data wiring.
     */
    public function index(Request $request)
    {
        // Placeholder data containers (to be replaced with real queries)
        $programs = null; // Can be a LengthAwarePaginator in real implementation
        $departments = [];

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'rows' => View::make('admin.programs.partials.table-rows', compact('programs'))->render(),
                'pagination' => View::make('admin.programs.partials.pagination', compact('programs'))->render(),
                'summary' => View::make('admin.programs.partials.summary', compact('programs'))->render(),
            ]);
        }

        return view('admin.programs.index', compact('programs', 'departments'));
    }
}
