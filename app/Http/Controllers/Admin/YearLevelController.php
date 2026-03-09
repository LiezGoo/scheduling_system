<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class YearLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = YearLevel::query();

        if ($request->filled('q')) {
            $search = '%' . trim((string) $request->input('q')) . '%';
            $query->where('name', 'LIKE', $search);
        }

        $yearLevels = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.year-levels.index', [
            'yearLevels' => $yearLevels,
            'filters' => [
                'q' => $request->input('q'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:year_levels,name'],
            'code' => ['nullable', 'string', 'max:20', 'unique:year_levels,code'],
            'status' => ['required', Rule::in([YearLevel::STATUS_ACTIVE, YearLevel::STATUS_INACTIVE])],
        ]);

        YearLevel::create([
            'name' => trim($validated['name']),
            'code' => isset($validated['code']) ? trim((string) $validated['code']) : null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.year-levels.index')->with('success', 'Year level created successfully.');
    }

    public function update(Request $request, YearLevel $yearLevel)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('year_levels', 'name')->ignore($yearLevel->id)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('year_levels', 'code')->ignore($yearLevel->id)],
            'status' => ['required', Rule::in([YearLevel::STATUS_ACTIVE, YearLevel::STATUS_INACTIVE])],
        ]);

        $yearLevel->update([
            'name' => trim($validated['name']),
            'code' => isset($validated['code']) ? trim((string) $validated['code']) : null,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.year-levels.index')->with('success', 'Year level updated successfully.');
    }

    public function destroy(YearLevel $yearLevel)
    {
        $yearLevel->delete();

        return redirect()->route('admin.year-levels.index')->with('success', 'Year level deleted successfully.');
    }
}
