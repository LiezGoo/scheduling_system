@extends('layouts.app')

@section('page-title', 'Curriculum Management')

@section('content')
    <div class="container-fluid py-4" id="curriculumPage">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="text-muted mb-0"><i class="fa fa-layer-group"></i> Manage program curricula and assigned subjects
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="#subjectSelection" class="btn btn-maroon" id="assignShortcutBtn">
                    <i class="fa-solid fa-plus me-2"></i>Assign Subjects
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filters / selection --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Program & Term</h5>
                        <p class="text-muted small mb-0">Pick a program, year level, and semester to enable subject
                            selection.</p>
                    </div>
                    <span class="badge bg-light text-dark">Step 1</span>
                </div>

                <form method="GET" action="{{ route('admin.curriculum.index') }}" id="programSelectForm" class="row g-3">
                    <div class="col-lg-5 col-md-6">
                        <label for="programSelect" class="form-label">Program <span class="text-danger">*</span></label>
                        <select name="program_id" id="programSelect" class="form-select" required>
                            <option value="">Select Program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}"
                                    {{ $selectedProgramId == $program->id ? 'selected' : '' }}>
                                    {{ $program->program_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3">
                        <label for="yearLevel" class="form-label">Year Level</label>
                        <select name="year_level" id="yearLevel" class="form-select">
                            <option value="1" {{ old('year_level', 1) == 1 ? 'selected' : '' }}>1st Year</option>
                            <option value="2" {{ old('year_level') == 2 ? 'selected' : '' }}>2nd Year</option>
                            <option value="3" {{ old('year_level') == 3 ? 'selected' : '' }}>3rd Year</option>
                            <option value="4" {{ old('year_level') == 4 ? 'selected' : '' }}>4th Year</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select name="semester" id="semester" class="form-select">
                            <option value="1st" {{ old('semester', '1st') === '1st' ? 'selected' : '' }}>1st Semester
                            </option>
                            <option value="2nd" {{ old('semester') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                            <option value="summer" {{ old('semester') === 'summer' ? 'selected' : '' }}>Summer</option>
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-12 d-flex align-items-end justify-content-end">
                        <button type="submit" class="btn btn-outline-secondary w-100" title="Load program">
                            <i class="fa-solid fa-rotate me-1"></i>Load
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Subject selection --}}
        <div class="card shadow-sm mb-4" id="subjectSelection">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Select Subjects</h5>
                        <p class="text-muted small mb-0">Choose one or more subjects to assign for the selected year and
                            semester.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" value="" id="selectAllSubjects">
                            <label class="form-check-label small" for="selectAllSubjects">Select all</label>
                        </div>
                        <span class="badge bg-light text-dark" id="subjectCountBadge">{{ $subjects->count() }}
                            subjects</span>
                        <span class="badge bg-light text-dark">Step 2</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.curriculum.store') }}" id="assignmentForm">
                    @csrf
                    <input type="hidden" name="program_id" id="programIdInput" value="{{ $selectedProgramId }}">
                    <input type="hidden" name="year_level" id="hiddenYearLevel" value="{{ old('year_level', 1) }}">
                    <input type="hidden" name="semester" id="hiddenSemester" value="{{ old('semester', '1st') }}">

                    <div class="table-responsive border rounded">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th class="text-center" style="width: 120px;">Units</th>
                                    <th class="text-end" style="width: 140px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $subject)
                                    @php
                                        $subjectKeyList = $assignedMatrix[$subject->id] ?? [];
                                        $currentKey = old('year_level', 1) . '|' . old('semester', '1st');
                                        $isAssigned = in_array($currentKey, $subjectKeyList, true);
                                    @endphp
                                    <tr class="subject-row {{ $isAssigned ? 'table-light' : '' }}">
                                        <td>
                                            <input type="checkbox" class="form-check-input subject-checkbox"
                                                name="subject_ids[]" value="{{ $subject->id }}"
                                                data-subject-id="{{ $subject->id }}"
                                                {{ in_array($subject->id, old('subject_ids', [])) ? 'checked' : '' }}
                                                {{ $isAssigned ? 'disabled' : '' }}>
                                        </td>
                                        <td class="fw-semibold">{{ $subject->subject_code }}</td>
                                        <td>{{ $subject->subject_name }}</td>
                                        <td class="text-center">{{ $subject->units ?? '—' }}</td>
                                        <td class="text-end">
                                            @if ($isAssigned)
                                                <span class="badge bg-secondary-subtle text-secondary">Already
                                                    assigned</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
                                            <div>No subjects found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                        <div class="text-muted small d-flex align-items-center gap-2">
                            <i class="fa-regular fa-circle-question"></i>
                            <span>Subjects already assigned for the selected term are disabled.</span>
                        </div>
                        <button type="submit" class="btn btn-maroon" id="assignButton" disabled>
                            <i class="fa-solid fa-paper-plane me-2"></i>Assign Selected Subjects
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Assigned subjects --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Assigned Subjects</h5>
                        <p class="text-muted small mb-0">Grouped by year level and semester.</p>
                    </div>
                    <span class="badge bg-light text-dark">Step 3</span>
                </div>
                @php
                    $semesterLabels = ['1st' => '1st Semester', '2nd' => '2nd Semester', 'summer' => 'Summer'];
                @endphp

                @forelse ($groupedCurriculum as $year => $semesters)
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-maroon text-white">Year {{ $year }}</span>
                        </div>
                        <div class="row g-3">
                            @foreach ($semesters as $semesterKey => $items)
                                <div class="col-xl-4 col-lg-6">
                                    <div class="border rounded h-100">
                                        <div
                                            class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                                            <span
                                                class="fw-semibold">{{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}</span>
                                            <span class="badge bg-light text-dark">{{ $items->count() }} subjects</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th class="text-center" style="width: 70px;">Units</th>
                                                        <th class="text-end" style="width: 90px;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($items as $subject)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $subject->subject_code }}</td>
                                                            <td>{{ $subject->subject_name }}</td>
                                                            <td class="text-center">{{ $subject->units ?? '—' }}</td>
                                                            <td class="text-end">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary" disabled
                                                                    title="Removal can be added later">
                                                                    <i class="fa-regular fa-trash-can"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="fa-regular fa-circle-xmark fa-3x mb-3"></i>
                        <p class="mb-0">No subjects assigned to this program yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: #fff;
        }

        .btn-maroon:hover {
            background-color: #550000;
            border-color: #550000;
            color: #fff;
        }

        .text-maroon {
            color: #660000;
        }

        .badge.bg-maroon {
            background-color: #660000;
        }

        .subject-row.table-light td {
            opacity: 0.85;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const assignedMatrix = @json($assignedMatrix);
            const programSelect = document.getElementById('programSelect');
            const yearLevel = document.getElementById('yearLevel');
            const semester = document.getElementById('semester');
            const hiddenYear = document.getElementById('hiddenYearLevel');
            const hiddenSemester = document.getElementById('hiddenSemester');
            const programIdInput = document.getElementById('programIdInput');
            const checkboxes = Array.from(document.querySelectorAll('.subject-checkbox'));
            const selectAll = document.getElementById('selectAllSubjects');
            const assignBtn = document.getElementById('assignButton');

            const currentKey = () => `${yearLevel.value}|${semester.value}`;

            const refreshCheckboxStates = () => {
                const key = currentKey();
                checkboxes.forEach((cb) => {
                    const subjectKeyList = assignedMatrix[cb.dataset.subjectId] || [];
                    const alreadyAssigned = subjectKeyList.includes(key);
                    cb.disabled = !programSelect.value || alreadyAssigned;
                    if (alreadyAssigned) {
                        cb.checked = false;
                        cb.closest('tr')?.classList.add('table-light');
                    } else {
                        cb.closest('tr')?.classList.remove('table-light');
                    }
                });
                selectAll.checked = false;
                syncAssignButtonState();
            };

            const syncAssignButtonState = () => {
                const anyChecked = checkboxes.some((cb) => cb.checked && !cb.disabled);
                assignBtn.disabled = !programSelect.value || !anyChecked;
            };

            selectAll?.addEventListener('change', (event) => {
                checkboxes.forEach((cb) => {
                    if (!cb.disabled) {
                        cb.checked = event.target.checked;
                    }
                });
                syncAssignButtonState();
            });

            checkboxes.forEach((cb) => {
                cb.addEventListener('change', syncAssignButtonState);
            });

            programSelect?.addEventListener('change', (event) => {
                programIdInput.value = event.target.value;
                refreshCheckboxStates();
            });

            yearLevel?.addEventListener('change', (event) => {
                hiddenYear.value = event.target.value;
                refreshCheckboxStates();
            });

            semester?.addEventListener('change', (event) => {
                hiddenSemester.value = event.target.value;
                refreshCheckboxStates();
            });

            // Initialize states on load
            refreshCheckboxStates();
            syncAssignButtonState();
        })();
    </script>
@endpush
