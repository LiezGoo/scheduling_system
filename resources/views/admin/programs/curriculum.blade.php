@extends('layouts.app')

@section('page-title', 'Curriculum Management')

@section('content')
    <div class="container-fluid py-4" id="curriculumPage">
        @php
            $indexRoute = route('admin.curriculum.index');
            $storeRoute = route('admin.curriculum.store');
            $selectedYearLevel = old('year_level', 1);
            $selectedSemester = old('semester', '1st');
            $selectedAcademicYearId = request('academic_year_id');
            $semesterOrder = ['1st', '2nd'];
            $semesterLabels = ['1st' => '1st Semester', '2nd' => '2nd Semester'];
            $yearLevels = [1, 2, 3, 4];
        @endphp

        <x-curriculum.page-header title="Curriculum Management" subtitle="Manage program subjects by year level and semester">
            <x-slot name="actions">
                <a href="#bulkAssignment" class="btn btn-maroon" id="assignShortcutBtn">
                    <i class="fa-solid fa-plus me-2"></i>Assign Subjects
                </a>
            </x-slot>
        </x-curriculum.page-header>

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

        <x-curriculum.filter-card title="Program Selection" subtitle="Select a program and optional academic year to load curriculum." badge="Step 1">
            <form method="GET" action="{{ $indexRoute }}" id="programSelectForm" class="row g-3 align-items-end" novalidate>
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
                    <label for="academicYear" class="form-label">Academic Year</label>
                    <select name="academic_year_id" id="academicYear" class="form-select">
                        <option value="">-- Select Academic Year --</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <button type="submit" class="btn btn-maroon w-100" title="Load curriculum">
                        <i class="fa-solid fa-rotate me-1"></i>Load Curriculum
                    </button>
                </div>
                <div class="col-lg-2 col-md-3">
                    <button type="button" class="btn btn-outline-secondary w-100" id="clearCurriculumFilters"
                        title="Clear Filters">
                        <i class="fa-solid fa-rotate-left me-1"></i>Clear
                    </button>
                </div>
            </form>
        </x-curriculum.filter-card>

        <x-curriculum.filter-card title="Bulk Subject Assignment" subtitle="Select subjects, year level, and semester for assignment." badge="Step 2">
            <form method="POST" action="{{ $storeRoute }}" id="assignmentForm">
                @csrf
                <input type="hidden" name="program_id" id="programIdInput" value="{{ $selectedProgramId }}">

                <div class="row g-4" id="bulkAssignment">
                    <div class="col-xl-7">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" value="" id="selectAllSubjects">
                                    <label class="form-check-label small" for="selectAllSubjects">Select all</label>
                                </div>
                                <span class="badge bg-light text-dark" id="subjectCountBadge">
                                    {{ $subjects->count() }} subjects
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="subjectSearch" class="form-label mb-0 small">Search</label>
                                <input type="search" id="subjectSearch" class="form-control form-control-sm"
                                    placeholder="Search by code or name">
                            </div>
                        </div>

                        <div class="table-responsive border rounded table-stack">
                            <table class="table align-middle mb-0" id="subjectsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th class="text-center" style="width: 120px;">Units</th>
                                        <th class="text-end" style="width: 160px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($subjects as $subject)
                                        @php
                                            $subjectKeyList = $assignedMatrix[$subject->id] ?? [];
                                            $currentKey = $selectedYearLevel . '|' . $selectedSemester;
                                            $isAssigned = in_array($currentKey, $subjectKeyList, true);
                                        @endphp
                                        <tr class="subject-row {{ $isAssigned ? 'table-light is-assigned' : '' }}"
                                            data-subject-code="{{ $subject->subject_code }}"
                                            data-subject-name="{{ $subject->subject_name }}">
                                            <td data-label="Select">
                                                <input type="checkbox" class="form-check-input subject-checkbox"
                                                    name="subject_ids[]" value="{{ $subject->id }}"
                                                    data-subject-id="{{ $subject->id }}"
                                                    {{ in_array($subject->id, old('subject_ids', [])) ? 'checked' : '' }}
                                                    {{ $isAssigned ? 'disabled' : '' }}>
                                            </td>
                                            <td class="fw-semibold" data-label="Subject Code">{{ $subject->subject_code }}</td>
                                            <td data-label="Subject Name">{{ $subject->subject_name }}</td>
                                            <td class="text-center" data-label="Units">{{ $subject->units ?? '—' }}</td>
                                            <td class="text-end" data-label="Status">
                                                <span class="badge bg-secondary-subtle text-secondary assigned-badge {{ $isAssigned ? '' : 'd-none' }}">
                                                    Already Assigned
                                                </span>
                                                <span class="badge bg-success-subtle text-success available-badge {{ $isAssigned ? 'd-none' : '' }}">
                                                    Available
                                                </span>
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
                            @error('subject_ids')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="bg-light rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">Assignment Details</h6>
                            <div class="mb-3">
                                <label for="assignYearLevel" class="form-label">Year Level</label>
                                <select name="year_level" id="assignYearLevel" class="form-select">
                                    @foreach ($yearLevels as $year)
                                        <option value="{{ $year }}" {{ (int) $selectedYearLevel === $year ? 'selected' : '' }}>
                                            {{ $year }}{{ $year === 1 ? 'st' : ($year === 2 ? 'nd' : ($year === 3 ? 'rd' : 'th')) }} Year
                                        </option>
                                    @endforeach
                                </select>
                                @error('year_level')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="assignSemester" class="form-label">Semester</label>
                                <select name="semester" id="assignSemester" class="form-select">
                                    @foreach ($semesterOrder as $semesterKey)
                                        <option value="{{ $semesterKey }}" {{ $selectedSemester === $semesterKey ? 'selected' : '' }}>
                                            {{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('semester')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <div class="alert alert-light border small mb-0" id="programSelectHint">
                                    <i class="fa-solid fa-circle-info me-2"></i>Select a program to enable assignment.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-maroon w-100" id="assignButton" disabled>
                                <i class="fa-solid fa-paper-plane me-2"></i>Assign Selected Subjects
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </x-curriculum.filter-card>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Curriculum Structure</h5>
                        <p class="text-muted small mb-0">Subjects grouped by year level and semester.</p>
                    </div>
                    <span class="badge bg-light text-dark">Step 3</span>
                </div>

                @if ($groupedCurriculum->isEmpty())
                    <x-curriculum.empty-state icon="fa-solid fa-book-open" title="No subjects assigned to this program yet."
                        subtitle="Use the assignment panel above to add subjects." />
                @else
                    <div class="accordion" id="curriculumAccordion">
                        @foreach ($yearLevels as $year)
                            @php
                                $semesters = $groupedCurriculum->get($year, collect());
                                $totalSubjects = $semesters->flatten(1)->count();
                                $accordionId = 'year-' . $year;
                            @endphp
                            <div class="accordion-item mb-3 border-0 shadow-sm">
                                <h2 class="accordion-header" id="{{ $accordionId }}-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}"
                                        aria-expanded="false" aria-controls="{{ $accordionId }}">
                                        <span class="badge bg-maroon me-2">Year {{ $year }}</span>
                                        <span class="text-muted small">{{ $totalSubjects }} subject{{ $totalSubjects !== 1 ? 's' : '' }}</span>
                                    </button>
                                </h2>
                                <div id="{{ $accordionId }}" class="accordion-collapse collapse"
                                    aria-labelledby="{{ $accordionId }}-header" data-bs-parent="#curriculumAccordion">
                                    <div class="accordion-body">
                                        <ul class="nav nav-pills mb-3" role="tablist">
                                            @foreach ($semesterOrder as $semesterKey)
                                                @php
                                                    $tabId = $accordionId . '-' . $semesterKey;
                                                    $semesterCount = $semesters->get($semesterKey, collect())->count();
                                                @endphp
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                        id="{{ $tabId }}-tab" data-bs-toggle="pill"
                                                        data-bs-target="#{{ $tabId }}" type="button" role="tab"
                                                        aria-controls="{{ $tabId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        <span class="badge bg-light text-dark me-2">{{ $semesterCount }}</span>
                                                        {{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <div class="tab-content">
                                            @foreach ($semesterOrder as $semesterKey)
                                                @php
                                                    $tabId = $accordionId . '-' . $semesterKey;
                                                    $items = $semesters->get($semesterKey, collect());
                                                @endphp
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                    id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                                                    @if ($items->isEmpty())
                                                        <div class="text-muted small py-3">
                                                            No subjects assigned for {{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}.
                                                        </div>
                                                    @else
                                                        <div class="table-responsive table-stack">
                                                            <table class="table table-hover align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Subject Code</th>
                                                                        <th>Subject Name</th>
                                                                        <th class="text-center" style="width: 90px;">Units</th>
                                                                        <th class="text-center" style="width: 120px;">Lecture</th>
                                                                        <th class="text-center" style="width: 120px;">Lab</th>
                                                                        <th class="text-end" style="width: 140px;">Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($items as $subject)
                                                                        <tr>
                                                                            <td class="fw-semibold" data-label="Subject Code">{{ $subject->subject_code }}</td>
                                                                            <td data-label="Subject Name">{{ $subject->subject_name }}</td>
                                                                            <td class="text-center" data-label="Units">{{ $subject->units ?? '—' }}</td>
                                                                            <td class="text-center" data-label="Lecture Hrs">{{ $subject->lecture_hours ?? '—' }}</td>
                                                                            <td class="text-center" data-label="Lab Hrs">{{ $subject->lab_hours ?? '—' }}</td>
                                                                            <td class="text-end" data-label="Actions">
                                                                                <div class="d-flex justify-content-end gap-2">
                                                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                                                        title="View" aria-label="View Subject Details">
                                                                                        <i class="fa-regular fa-eye"></i>
                                                                                    </button>
                                                                                    <button type="button" class="btn btn-sm btn-outline-warning" disabled
                                                                                        title="Edit" aria-label="Edit Subject">
                                                                                        <i class="fa-solid fa-pencil"></i>
                                                                                    </button>
                                                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#removeSubjectModal"
                                                                                        data-subject-name="{{ $subject->subject_name }}"
                                                                                        data-subject-code="{{ $subject->subject_code }}"
                                                                                        data-year-level="{{ $year }}"
                                                                                        data-semester="{{ $semesterKey }}"
                                                                                        title="Delete" aria-label="Remove Subject From Curriculum">
                                                                                        <i class="fa-solid fa-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-curriculum.confirm-modal id="removeSubjectModal" title="Remove Subject" confirmLabel="Remove"
        confirmClass="btn-danger">
        <p class="mb-2">
            You are about to remove <span class="fw-semibold" id="removeSubjectName">this subject</span>
            from <span class="fw-semibold" id="removeSubjectTerm">the selected term</span>.
        </p>
        <div class="alert alert-warning small mb-0">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>This action only affects the curriculum assignment.
        </div>
    </x-curriculum.confirm-modal>
@endsection

@push('styles')
    <style>
        .bg-maroon {
            background-color: #660000 !important;
        }

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

        .empty-state-icon {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: rgba(102, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #660000;
            font-size: 2rem;
        }

        .subject-row.is-assigned td {
            opacity: 0.8;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 0.35rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .table-stack thead {
                display: none;
            }

            .table-stack tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: 12px;
                margin-bottom: 0.75rem;
                padding: 0.5rem 0.75rem;
            }

            .table-stack tbody td {
                display: flex;
                justify-content: space-between;
                padding: 0.35rem 0;
                border: none;
            }

            .table-stack tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const assignedMatrix = @json($assignedMatrix);
            const programSelect = document.getElementById('programSelect');
            const academicYear = document.getElementById('academicYear');
            const clearFiltersBtn = document.getElementById('clearCurriculumFilters');
            const subjectSearch = document.getElementById('subjectSearch');
            const yearLevel = document.getElementById('assignYearLevel');
            const semester = document.getElementById('assignSemester');
            const programIdInput = document.getElementById('programIdInput');
            const checkboxes = Array.from(document.querySelectorAll('.subject-checkbox'));
            const selectAll = document.getElementById('selectAllSubjects');
            const assignBtn = document.getElementById('assignButton');
            const subjectCountBadge = document.getElementById('subjectCountBadge');
            const programSelectHint = document.getElementById('programSelectHint');

            const currentKey = () => `${yearLevel.value}|${semester.value}`;

            const refreshCheckboxStates = () => {
                const key = currentKey();
                checkboxes.forEach((cb) => {
                    const row = cb.closest('tr');
                    const subjectKeyList = assignedMatrix[cb.dataset.subjectId] || [];
                    const alreadyAssigned = subjectKeyList.includes(key);
                    cb.disabled = !programSelect.value || alreadyAssigned;
                    if (alreadyAssigned) {
                        cb.checked = false;
                        row?.classList.add('table-light', 'is-assigned');
                    } else {
                        row?.classList.remove('table-light', 'is-assigned');
                    }
                    const assignedBadge = row?.querySelector('.assigned-badge');
                    const availableBadge = row?.querySelector('.available-badge');
                    if (assignedBadge && availableBadge) {
                        assignedBadge.classList.toggle('d-none', !alreadyAssigned);
                        availableBadge.classList.toggle('d-none', alreadyAssigned);
                    }
                });
                selectAll.checked = false;
                syncAssignButtonState();
                updateHint();
            };

            const syncAssignButtonState = () => {
                const anyChecked = checkboxes.some((cb) => cb.checked && !cb.disabled);
                assignBtn.disabled = !programSelect.value || !anyChecked;
            };

            const updateHint = () => {
                if (!programSelect.value) {
                    programSelectHint.classList.remove('d-none');
                } else {
                    programSelectHint.classList.add('d-none');
                }
            };

            const updateSubjectCount = () => {
                const visibleRows = checkboxes.filter((cb) => !cb.closest('tr')?.classList.contains('d-none'));
                subjectCountBadge.textContent = `${visibleRows.length} subjects`;
            };

            selectAll?.addEventListener('change', (event) => {
                checkboxes.forEach((cb) => {
                    const row = cb.closest('tr');
                    if (!cb.disabled && !row?.classList.contains('d-none')) {
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

            yearLevel?.addEventListener('change', refreshCheckboxStates);
            semester?.addEventListener('change', refreshCheckboxStates);

            subjectSearch?.addEventListener('input', (event) => {
                const query = event.target.value.toLowerCase();
                checkboxes.forEach((cb) => {
                    const row = cb.closest('tr');
                    const code = row?.dataset.subjectCode?.toLowerCase() || '';
                    const name = row?.dataset.subjectName?.toLowerCase() || '';
                    const matches = code.includes(query) || name.includes(query);
                    row?.classList.toggle('d-none', !matches);
                });
                updateSubjectCount();
                selectAll.checked = false;
            });

            clearFiltersBtn?.addEventListener('click', () => {
                if (programSelect) programSelect.value = '';
                if (academicYear) academicYear.value = '';
                document.getElementById('programSelectForm')?.submit();
            });

            document.querySelectorAll('.remove-subject-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const subjectName = btn.dataset.subjectName || 'this subject';
                    const subjectCode = btn.dataset.subjectCode || '';
                    const year = btn.dataset.yearLevel || '';
                    const semesterLabel = btn.dataset.semester || '';
                    const nameTarget = document.getElementById('removeSubjectName');
                    const termTarget = document.getElementById('removeSubjectTerm');
                    if (nameTarget) {
                        nameTarget.textContent = subjectCode ? `${subjectName} (${subjectCode})` : subjectName;
                    }
                    if (termTarget) {
                        termTarget.textContent = `Year ${year}, ${semesterLabel}`;
                    }
                });
            });

            refreshCheckboxStates();
            updateSubjectCount();
        })();
    </script>
@endpush
