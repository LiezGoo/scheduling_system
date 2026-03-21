<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of subjects for the department head's department.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('viewAny', Subject::class);

        // Department heads can only VIEW subjects from their department
        if (!$user->isDepartmentHead()) {
            abort(403, 'Unauthorized access.');
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $query = Subject::with(['department', 'creator'])
            ->forDepartment($department->id);

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
        }

        // Filter by active status (default: show all)
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered subjects
        $subjects = $query->orderBy('subject_code')->paginate($perPage)->appends($request->query());

        // Get department information
        $departmentName = $department->department_name;

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('department-head.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => (string) $subjects->withQueryString()->links(),
            ]);
        }

        return view('department-head.subjects.index', [
            'subjects' => $subjects,
            'departmentName' => $departmentName,
        ]);
    }

    /**
     * Display the specified subject details.
     */
    public function show(Request $request, Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('view', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to department head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

        // Check if this is an AJAX request for JSON details
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subject' => [
                    'id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                    'units' => $subject->units,
                    'lecture_hours' => $subject->lecture_hours,
                    'lab_hours' => $subject->lab_hours,
                    'description' => $subject->description,
                    'is_active' => $subject->is_active,
                    'department_name' => $subject->department->department_name,
                    'created_by' => $subject->creator->full_name ?? 'System',
                ]
            ]);
        }

        $subject->load(['department', 'creator']);
        return view('department-head.subjects.show', compact('subject'));
    }

    /**
     * Store a newly created subject in the department head's department.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('create', Subject::class);

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')->where('department_id', $department->id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')->where('department_id', $department->id),
            ],
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Lecture hours and lab hours cannot both be 0.',
            ], 422);
        }

        try {
            // Automatically set department_id and created_by
            $validated['department_id'] = $department->id;
            $validated['created_by'] = $user->id;
            $validated['is_active'] = $request->boolean('is_active');

            $subject = Subject::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully!',
                'subject' => $subject->load('department'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified subject in the department head's department.
     */
    public function update(Request $request, Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('update', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to department head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')
                    ->where('department_id', $department->id)
                    ->ignore($subject->id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')
                    ->where('department_id', $department->id)
                    ->ignore($subject->id),
            ],
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Lecture hours and lab hours cannot both be 0.',
            ], 422);
        }

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $subject->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully!',
                'subject' => $subject->load('department'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove/deactivate the specified subject in the department head's department.
     */
    public function destroy(Request $request, Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('delete', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to department head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $subject->delete();

            if (!$request->expectsJson() && !$request->ajax()) {
                return redirect()
                    ->route('department-head.subjects.index')
                    ->with('success', 'Subject deleted successfully!');
            }

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully!',
            ]);
        } catch (\Exception $e) {
            if (!$request->expectsJson() && !$request->ajax()) {
                return redirect()
                    ->route('department-head.subjects.index')
                    ->with('error', 'Failed to delete subject: ' . $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CSV template for bulk subject imports.
     */
    public function downloadCsvTemplate()
    {
        $headers = [
            'subject_code',
            'subject_name',
            'units',
            'lecture_hours',
            'lab_hours',
        ];

        $sampleRows = [
            ['CS101', 'Introduction to Programming', '3', '2', '1'],
            ['IT202', 'Database Systems', '3', '2', '1'],
            ['ENG101', 'Communication Skills', '3', '3', '0'],
        ];

        $callback = function () use ($headers, $sampleRows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($sampleRows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        };

        return response()->streamDownload($callback, 'subjects-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Upload and process subject CSV for bulk import.
     */
    public function uploadCsv(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('create', Subject::class);

        $department = $user->getInferredDepartment();
        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $validatedRequest = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'fail_on_error' => ['nullable', 'boolean'],
        ]);

        $failOnError = (bool) ($validatedRequest['fail_on_error'] ?? false);
        $file = $validatedRequest['csv_file'];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read CSV file.',
            ], 422);
        }

        $rawHeaders = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $rawHeaders);

        $expectedHeaders = ['subject_code', 'subject_name', 'units', 'lecture_hours', 'lab_hours'];
        if ($headers !== $expectedHeaders) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Invalid CSV format. Expected columns: subject_code, subject_name, units, lecture_hours, lab_hours',
            ], 422);
        }

        $insertedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $seenSubjectCodes = [];

        DB::beginTransaction();

        try {
            $rowNumber = 1; // Header row

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                $rowData = $this->mapCsvRow($headers, $row);
                $rowData = $this->normalizeCsvRow($rowData);

                if (count($row) < count($expectedHeaders)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'Row has missing column values.',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                $rowValidator = Validator::make($rowData, [
                    'subject_code' => ['required', 'string', 'max:50'],
                    'subject_name' => ['required', 'string', 'max:255'],
                    'units' => ['required', 'integer', 'min:0', 'max:10'],
                    'lecture_hours' => ['required', 'integer', 'min:0', 'max:20'],
                    'lab_hours' => ['required', 'integer', 'min:0', 'max:20'],
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => $rowValidator->errors()->first(),
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                if ((int) $rowData['lecture_hours'] <= 0 && (int) $rowData['lab_hours'] <= 0) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'lecture_hours and lab_hours cannot both be 0',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                if (((int) $rowData['lecture_hours'] + (int) $rowData['lab_hours']) < (int) $rowData['units']) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'Total lecture_hours and lab_hours must be greater than or equal to units',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                $normalizedCode = strtolower((string) $rowData['subject_code']);
                if (isset($seenSubjectCodes[$normalizedCode])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'subject_code is duplicated in the CSV file',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                $seenSubjectCodes[$normalizedCode] = true;

                $duplicateCode = Subject::query()
                    ->where('department_id', $department->id)
                    ->where('subject_code', $rowData['subject_code'])
                    ->exists();

                if ($duplicateCode) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'subject_code already exists',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                $duplicateName = Subject::query()
                    ->where('department_id', $department->id)
                    ->where('subject_name', $rowData['subject_name'])
                    ->exists();

                if ($duplicateName) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'reason' => 'subject_name already exists',
                    ];
                    $skippedCount++;

                    if ($failOnError) {
                        break;
                    }

                    continue;
                }

                $subject = Subject::create([
                    'subject_code' => $rowData['subject_code'],
                    'subject_name' => $rowData['subject_name'],
                    'units' => (int) $rowData['units'],
                    'lecture_hours' => (int) $rowData['lecture_hours'],
                    'lab_hours' => (int) $rowData['lab_hours'],
                    'description' => null,
                    'is_active' => true,
                    'department_id' => $department->id,
                    'created_by' => $user->id,
                ]);

                $insertedCount++;
            }

            fclose($handle);

            if ($failOnError && !empty($errors)) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'CSV import failed. No records were inserted because fail_on_error is enabled.',
                    'inserted' => 0,
                    'skipped' => $skippedCount,
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CSV import completed.',
                'inserted' => $insertedCount,
                'skipped' => $skippedCount,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process CSV: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function mapCsvRow(array $headers, array $row): array
    {
        $rowData = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $rowData[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $rowData;
    }

    private function normalizeCsvRow(array $rowData): array
    {
        foreach (['subject_code', 'subject_name'] as $key) {
            if (isset($rowData[$key])) {
                $rowData[$key] = trim((string) $rowData[$key]);
            }
        }

        return $rowData;
    }

    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
