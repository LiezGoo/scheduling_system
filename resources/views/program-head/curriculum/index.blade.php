@extends('layouts.app')

@section('page-title', 'Curriculum Management')

@section('content')
    <div class="container-fluid py-4" id="curriculumPage">
        @php
            $indexRoute = route('program-head.curriculum.index');
            $storeRoute = route('program-head.curriculum.store');
            $selectedYearLevel = old('year_level', 1);
            $selectedSemester = old('semester', '1st');
            $selectedAcademicYearId = request('academic_year_id');
            $semesterOrder = ['1st', '2nd'];
            $semesterLabels = ['1st' => '1st Semester', '2nd' => '2nd Semester'];
            $yearLevels = [1, 2, 3, 4];
        @endphp

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Validation Error!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Loading Indicator -->
        <div id="curriculumLoading" class="alert alert-info alert-dismissible fade d-none" role="alert">
            <div class="spinner-border spinner-border-sm me-2" role="status" style="display: inline-block;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <strong>Loading curriculum...</strong> Please wait while we fetch your curriculum data.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- STEP 1: Program Selection Section -->
        <div class="card shadow-sm border-0 mb-5 curriculum-card">
            <div class="card-header bg-maroon text-white border-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-maroon rounded-circle step-badge">1</span>
                    <div>
                        <h6 class="mb-0 fw-semibold fs-5">Program Selection</h6>
                        <small class="text-white-50">Choose a program to manage</small>
                    </div>
                    <i class="fa-solid fa-school fa-lg opacity-25 ms-auto"></i>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="{{ $indexRoute }}" id="programSelectForm" novalidate>
                    <!-- Main Selection Row -->
                    <div class="row g-4 align-items-end">
                        <!-- Program Selection -->
                        <div class="col-md-6">
                            <label for="programSelect" class="form-label fw-semibold small mb-2 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-graduation-cap text-maroon"></i>
                                Program <span class="text-danger">*</span>
                            </label>
                            <select name="program_id" id="programSelect" class="form-select form-select-md rounded-2" required>
                                <option value="">-- Select a Program --</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}"
                                        {{ $selectedProgramId == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Academic Year Selection -->
                        <div class="col-md-6">
                            <label for="academicYear" class="form-label fw-semibold small mb-2 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calendar text-maroon"></i>
                                Academic Year
                            </label>
                            <select name="academic_year_id" id="academicYear" class="form-select form-select-md rounded-2">
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="row g-3 mt-1">
                        <div class="col-auto ms-auto">
                            <div class="btn-group" role="group">
                                <button type="submit" class="btn btn-maroon fw-semibold px-4" title="Load curriculum for selected program">
                                    <i class="fa-solid fa-rotate me-2"></i><span class="d-none d-sm-inline">Load Curriculum</span><span class="d-sm-none">Load</span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary fw-semibold px-4" id="clearCurriculumFilters"
                                    title="Clear all filters and reset">
                                    <i class="fa-solid fa-broom me-2"></i><span class="d-none d-sm-inline">Clear</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- STEP 2: Bulk Subject Assignment Section -->
        <div class="card shadow-sm border-0 mb-5 curriculum-card">
            <div class="card-header bg-maroon text-white border-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-maroon rounded-circle step-badge">2</span>
                    <div>
                        <h6 class="mb-0 fw-semibold fs-5">Bulk Subject Assignment</h6>
                        <small class="text-white-50">Assign subjects to year levels and semesters</small>
                    </div>
                    <i class="fa-solid fa-tasks fa-lg opacity-25 ms-auto"></i>
                </div>
            </div>
            <div class="card-body p-4" id="bulkAssignment">
                <form method="POST" action="{{ $storeRoute }}" id="assignmentForm">
                    @csrf
                    <input type="hidden" name="program_id" id="programIdInput" value="{{ $selectedProgramId }}">

                    <div class="row g-4">
                        <!-- Subjects List -->
                        <div class="col-lg-8">
                            <!-- Search and Select All Row -->
                            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input form-check-input-lg" type="checkbox" value="" id="selectAllSubjects">
                                        <label class="form-check-label fw-semibold" for="selectAllSubjects">
                                            Select All
                                        </label>
                                    </div>
                                    <span class="badge bg-maroon-light text-maroon px-3 py-2 rounded-2" id="subjectCountBadge">
                                        {{ $subjects->count() }} subjects
                                    </span>
                                </div>
                                <div class="input-group flex-grow-1 flex-md-grow-0" style="max-width: 350px;">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-2">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="search" id="subjectSearch" class="form-control border-start-0 rounded-end-2"
                                        placeholder="Search by code or name">
                                </div>
                            </div>

                            <!-- Subjects Table -->
                            <div class="border rounded-3 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" id="subjectsTable">
                                        <thead class="table-light border-bottom">
                                            <tr>
                                                <th style="width: 50px;" class="text-center">
                                                    <i class="fa-solid fa-check-double text-muted fw-normal"></i>
                                                </th>
                                                <th class="fw-semibold small">
                                                    <i class="fa-solid fa-bookmark text-maroon me-2"></i>Code
                                                </th>
                                                <th class="fw-semibold small">
                                                    <i class="fa-solid fa-book text-maroon me-2"></i>Subject Name
                                                </th>
                                                <th class="text-center fw-semibold small" style="width: 100px;">
                                                    <i class="fa-solid fa-square text-maroon me-1"></i>Units
                                                </th>
                                                <th class="text-end fw-semibold small" style="width: 160px;">
                                                    <i class="fa-solid fa-tag text-maroon me-2"></i>Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="subject-table-body">
                                            @forelse ($subjects as $subject)
                                                @php
                                                    $subjectKeyList = $assignedMatrix[$subject->id] ?? [];
                                                    $currentKey = $selectedYearLevel . '|' . $selectedSemester;
                                                    $isAssigned = in_array($currentKey, $subjectKeyList, true);
                                                @endphp
                                                <tr class="subject-row transition-all {{ $isAssigned ? 'subject-assigned opacity-65' : '' }}"
                                                    data-subject-code="{{ $subject->subject_code }}"
                                                    data-subject-name="{{ $subject->subject_name }}"
                                                    style="--transition-duration: 150ms;">
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input form-check-input-lg subject-checkbox"
                                                            name="subject_ids[]" value="{{ $subject->id }}"
                                                            data-subject-id="{{ $subject->id }}"
                                                            {{ in_array($subject->id, old('subject_ids', [])) ? 'checked' : '' }}
                                                            {{ $isAssigned ? 'disabled' : '' }}>
                                                    </td>
                                                    <td class="fw-bold text-maroon small">{{ $subject->subject_code }}</td>
                                                    <td class="small">{{ $subject->subject_name }}</td>
                                                    <td class="text-center fw-semibold">
                                                        <span class="badge bg-light text-dark">{{ $subject->units ?? '—' }}</span>
                                                    </td>
                                                    <td class="text-end">
                                                        @if($isAssigned)
                                                            <span class="badge bg-secondary text-white px-3 py-2 rounded-2">
                                                                <i class="fa-solid fa-check-circle me-1"></i>
                                                                <span class="d-none d-sm-inline">Already Assigned</span><span class="d-sm-none">Assigned</span>
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success text-white px-3 py-2 rounded-2">
                                                                <i class="fa-solid fa-check me-1"></i>
                                                                <span class="d-none d-sm-inline">Available</span>
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-5">
                                                        <i class="fa-regular fa-folder-open fa-3x mb-3 opacity-50"></i>
                                                        <div class="fw-semibold mb-1">No subjects found</div>
                                                        <small>Select a program to view available subjects</small>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Help Text -->
                            <div class="alert alert-info border-0 mt-3 mb-0 rounded-2">
                                <i class="fa-solid fa-circle-info text-info me-2"></i>
                                <small class="text-info">Subjects already assigned for the selected term are <strong>disabled</strong>. Only available subjects can be selected.</small>
                            </div>

                            @error('subject_ids')
                                <div class="alert alert-danger border-0 mt-3 mb-0 rounded-2">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Assignment Details Card -->
                        <div class="col-lg-4">
                            <div class="card border-2 border-maroon shadow-sm h-100 rounded-3">
                                <div class="card-header bg-light border-bottom border-maroon p-3">
                                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-sliders text-maroon"></i>
                                        <span>Assignment Settings</span>
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Year Level -->
                                    <div class="mb-4">
                                        <label for="assignYearLevel" class="form-label fw-semibold small mb-2 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-layer-group text-maroon"></i>Year Level
                                        </label>
                                        <select name="year_level" id="assignYearLevel" class="form-select form-select-md rounded-2" style="border-color: #d6d6d6;">
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

                                    <!-- Semester -->
                                    <div class="mb-4">
                                        <label for="assignSemester" class="form-label fw-semibold small mb-2 d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-calendar-days text-maroon"></i>Semester
                                        </label>
                                        <select name="semester" id="assignSemester" class="form-select form-select-md rounded-2" style="border-color: #d6d6d6;">
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

                                    <!-- Summary Section -->
                                    <div class="bg-maroon-light rounded-2 p-3 mb-4 border border-maroon border-opacity-25">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fa-solid fa-info-circle me-1"></i>Selected Subjects:
                                        </small>
                                        <h5 class="mb-0 text-maroon fw-bold">
                                            <span id="selectedCount">0</span>
                                            <small class="text-muted fw-normal" id="selectedLabel">subjects</small>
                                        </h5>
                                    </div>

                                    <!-- Primary Action Button -->
                                    <button type="submit" class="btn btn-maroon fw-semibold w-100 mb-2 rounded-2" id="assignButton" disabled>
                                        <i class="fa-solid fa-paper-plane me-2"></i><span class="d-none d-sm-inline">Assign Selected Subjects</span><span class="d-sm-none">Assign</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- STEP 3: Curriculum Structure Section -->
        <div class="card shadow-sm border-0 mb-5 curriculum-card">
            <div class="card-header bg-maroon text-white border-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-maroon rounded-circle step-badge">3</span>
                    <div>
                        <h6 class="mb-0 fw-semibold fs-5">Curriculum Structure</h6>
                        <small class="text-white-50">View subjects organized by year and semester</small>
                    </div>
                    <i class="fa-solid fa-sitemap fa-lg opacity-25 ms-auto"></i>
                </div>
            </div>
            <div class="card-body p-4">

                @if ($groupedCurriculum->isEmpty())
                    <x-curriculum.empty-state 
                        icon="fa-solid fa-book-open" 
                        title="No subjects assigned yet"
                        subtitle="Start by selecting a program and assigning subjects from the sections above." />
                @else
                    <div class="accordion accordion-flush" id="curriculumAccordion">
                        @foreach ($yearLevels as $year)
                            @php
                                $semesters = $groupedCurriculum->get($year, collect());
                                $totalSubjects = $semesters->flatten(1)->count();
                                $accordionId = 'year-' . $year;
                            @endphp
                            <div class="accordion-item mb-3 rounded-3 shadow-sm overflow-hidden curriculum-year-card border-0">
                                <h2 class="accordion-header p-0" id="{{ $accordionId }}-header">
                                    <button class="accordion-button collapsed fw-semibold py-4 px-5 rounded-3" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}"
                                        aria-expanded="false" aria-controls="{{ $accordionId }}">
                                        <span class="badge bg-maroon rounded-circle me-3 year-badge">Y{{ $year }}</span>
                                        <span class="flex-grow-1 text-start">
                                            <strong class="fs-5 me-2">Year {{ $year }}</strong>
                                            <span class="badge bg-maroon-light text-maroon rounded-2 small">{{ $totalSubjects }} subject{{ $totalSubjects !== 1 ? 's' : '' }}</span>
                                        </span>
                                        <i class="fa-solid fa-chevron-down text-muted small accordion-arrow"></i>
                                    </button>
                                </h2>
                                <div id="{{ $accordionId }}" class="accordion-collapse collapse"
                                    aria-labelledby="{{ $accordionId }}-header" data-bs-parent="#curriculumAccordion">
                                    <div class="accordion-body p-4 bg-light border-top">
                                        <!-- Semester Tabs -->
                                        <ul class="nav nav-pills gap-2 mb-4 flex-wrap" role="tablist">
                                            @foreach ($semesterOrder as $index => $semesterKey)
                                                @php
                                                    $tabId = $accordionId . '-' . $semesterKey;
                                                    $semesterCount = $semesters->get($semesterKey, collect())->count();
                                                @endphp
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link fw-semibold small transition-all {{ $index === 0 ? 'active' : '' }}"
                                                        id="{{ $tabId }}-tab" data-bs-toggle="pill"
                                                        data-bs-target="#{{ $tabId }}" type="button" role="tab"
                                                        aria-controls="{{ $tabId }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                                        <span class="badge bg-maroon-light text-maroon me-2 rounded-2">{{ $semesterCount }}</span>
                                                        {{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <!-- Tab Content -->
                                        <div class="tab-content">
                                            @foreach ($semesterOrder as $index => $semesterKey)
                                                @php
                                                    $tabId = $accordionId . '-' . $semesterKey;
                                                    $items = $semesters->get($semesterKey, collect());
                                                @endphp
                                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                                    id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">
                                                    @if ($items->isEmpty())
                                                        <div class="text-center py-5">
                                                            <i class="fa-solid fa-inbox fa-3x text-muted opacity-50 mb-3"></i>
                                                            <p class="text-muted">No subjects assigned for {{ $semesterLabels[$semesterKey] ?? ucfirst($semesterKey) }}</p>
                                                        </div>
                                                    @else
                                                        <div class="border rounded-3 overflow-hidden bg-white">
                                                            <div class="table-responsive">
                                                                <table class="table table-hover align-middle mb-0 curriculum-table">
                                                                    <thead class="table-light border-bottom">
                                                                        <tr>
                                                                            <th class="fw-semibold small">
                                                                                <i class="fa-solid fa-bookmark text-maroon me-2"></i>Code
                                                                            </th>
                                                                            <th class="fw-semibold small">
                                                                                <i class="fa-solid fa-book text-maroon me-2"></i>Subject Name
                                                                            </th>
                                                                            <th class="text-center fw-semibold small" style="width: 90px;">
                                                                                <i class="fa-solid fa-square-root-variable text-maroon me-1"></i>Units
                                                                            </th>
                                                                            <th class="text-center fw-semibold small" style="width: 100px;">
                                                                                <i class="fa-solid fa-chalkboard-user text-maroon me-1"></i>Lec
                                                                            </th>
                                                                            <th class="text-center fw-semibold small" style="width: 100px;">
                                                                                <i class="fa-solid fa-flask text-maroon me-1"></i>Lab
                                                                            </th>
                                                                            <th class="text-end fw-semibold small" style="width: 140px;">
                                                                                <i class="fa-solid fa-sliders text-maroon me-1"></i>Actions
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($items as $subject)
                                                                            <tr class="curriculum-subject-row transition-all">
                                                                                <td class="fw-bold text-maroon small">{{ $subject->subject_code }}</td>
                                                                                <td class="small">{{ $subject->subject_name }}</td>
                                                                                <td class="text-center">
                                                                                    <span class="badge bg-light text-dark rounded-2">{{ $subject->units ?? '—' }}</span>
                                                                                </td>
                                                                                <td class="text-center small">
                                                                                    {{ $subject->lecture_hours ?? '—' }}
                                                                                </td>
                                                                                <td class="text-center small">
                                                                                    {{ $subject->lab_hours ?? '—' }}
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <div class="d-flex justify-content-end gap-2">
                                                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn transition-all"
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
        confirmClass="btn-maroon">
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
        /* ==== COLOR SCHEME ==== */
        :root {
            --maroon: #660000;
            --maroon-light: rgba(102, 0, 0, 0.1);
            --maroon-dark: #550000;
            --maroon-soft: rgba(102, 0, 0, 0.05);
        }

        .bg-maroon {
            background-color: var(--maroon) !important;
        }

        .bg-maroon-light {
            background-color: var(--maroon-light) !important;
        }

        .bg-maroon-soft {
            background-color: var(--maroon-soft);
        }

        .btn-maroon {
            background-color: var(--maroon);
            border-color: var(--maroon);
            color: #fff;
        }

        .btn-maroon:hover,
        .btn-maroon:focus {
            background-color: var(--maroon-dark);
            border-color: var(--maroon-dark);
            color: #fff;
        }

        .btn-maroon:disabled {
            background-color: var(--maroon);
            opacity: 0.5;
        }

        .text-maroon {
            color: var(--maroon);
        }

        .border-maroon {
            border-color: var(--maroon) !important;
        }

        .badge.bg-maroon {
            background-color: var(--maroon);
            color: #fff;
        }

        .badge.bg-maroon-light {
            background-color: var(--maroon-light);
            color: var(--maroon);
        }

        /* ==== LAYOUT & SPACING ==== */
        #curriculumPage {
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
        }

        /* ==== CARDS ==== */
        .curriculum-card {
            border-radius: 1rem !important;
            transition: all 250ms ease;
            overflow: hidden;
        }

        .curriculum-card:hover {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1) !important;
        }

        .curriculum-card .card-header {
            border-radius: 0 !important;
            padding: 1.5rem !important;
            border-bottom: none;
        }

        .curriculum-card .card-body {
            padding: 2rem !important;
        }

        .card.border-maroon {
            border-width: 2px !important;
        }

        /* ==== STEP BADGES ==== */
        .step-badge {
            width: 40px;
            height: 40px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }

        .year-badge {
            width: 42px;
            height: 42px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.05rem;
        }

        /* ==== FORMS ==== */
        .form-select,
        .form-control {
            border-radius: 0.625rem;
            transition: all 200ms ease;
            border: 1px solid #d6d6d6;
        }

        .form-select-md,
        .form-control-md {
            padding: 0.6rem 0.875rem;
            font-size: 0.95rem;
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 0.2rem var(--maroon-light);
        }

        .form-label {
            font-size: 0.95rem;
            color: #2c3e50;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0.2rem;
            cursor: pointer;
            transition: all 150ms ease;
            border: 2px solid #d6d6d6;
        }

        .form-check-input-lg {
            width: 24px;
            height: 24px;
        }

        .form-check-input:checked {
            background-color: var(--maroon);
            border-color: var(--maroon);
        }

        .form-check-input:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 0.25rem var(--maroon-light);
        }

        .form-check-input:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        /* ==== TABLES ==== */
        .table {
            --bs-table-hover-bg: rgba(102, 0, 0, 0.05);
            font-size: 0.95rem;
        }

        .table tbody tr {
            transition: all 150ms ease;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: var(--maroon-light);
        }

        .table th {
            padding: 1rem 0.875rem !important;
            font-weight: 700;
            vertical-align: middle;
        }

        .table td {
            padding: 0.875rem !important;
            vertical-align: middle;
        }

        .curriculum-table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .curriculum-table thead th {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .subject-table-body tr:hover {
            background-color: var(--maroon-light) !important;
        }

        .subject-table-body tr.subject-assigned {
            opacity: 0.65;
            background-color: #f5f5f5 !important;
        }

        .subject-table-body tr td {
            transition: all 150ms ease;
        }

        /* ==== BUTTONS ==== */
        .btn {
            border-radius: 0.625rem;
            transition: all 150ms ease;
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            border: none;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ==== BADGES ==== */
        .badge {
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 600;
            padding: 0.4rem 0.6rem;
        }

        .badge.rounded-2 {
            border-radius: 0.5rem;
        }

        .badge.rounded-circle {
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge.px-3,
        .badge.px-3.py-2 {
            padding: 0.5rem 0.875rem !important;
        }

        .bg-success {
            background-color: #198754 !important;
            color: #fff !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
        }

        /* ==== ACCORDION ==== */
        .accordion-button {
            background-color: #fff;
            border: none;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem !important;
            transition: all 250ms ease;
            font-weight: 600;
            color: #2c3e50;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--maroon-light);
            color: var(--maroon);
            box-shadow: none;
            border-color: transparent;
        }

        .accordion-button:focus {
            border-color: var(--maroon);
            box-shadow: 0 0 0 0.25rem var(--maroon-light);
        }

        .accordion-button::after {
            transition: transform 300ms ease;
        }

        .accordion-button:not(.collapsed)::after {
            transform: rotate(-180deg);
        }

        .accordion-body {
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem !important;
        }

        .accordion-item {
            background-color: #fff;
            border: none;
            overflow: hidden;
        }

        .curriculum-year-card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 250ms ease;
        }

        .curriculum-year-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .accordion-header {
            padding: 0 !important;
        }

        .accordion-arrow {
            transition: transform 300ms ease;
        }

        /* ==== ALERTS ==== */
        .alert {
            border-radius: 0.625rem;
            border: 1px solid;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #842029;
        }

        /* ==== EMPTY STATE ==== */
        .empty-state-icon {
            width: 90px;
            height: 90px;
            border-radius: 1.25rem;
            background: var(--maroon-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--maroon);
            font-size: 2.75rem;
        }

        /* ==== TRANSITIONS ==== */
        .transition-all {
            transition: all var(--transition-duration, 200ms) ease;
        }

        /* ==== MODAL ==== */
        .modal-header {
            border-bottom: 1px solid #e9ecef;
            background-color: #f8f9fa;
        }

        .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }


        /* ==== RESPONSIVE ==== */
        @media (max-width: 1024px) {
            .curriculum-card .card-body {
                padding: 1.75rem !important;
            }

            .form-select-md,
            .form-control-md {
                padding: 0.55rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 992px) {
            .curriculum-card .card-body {
                padding: 1.5rem !important;
            }

            .curriculum-card .card-header {
                padding: 1.25rem !important;
            }

            .table th {
                padding: 0.875rem !important;
            }

            .table td {
                padding: 0.875rem !important;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            #curriculumPage {
                --spacing-md: 1rem;
                --spacing-lg: 1.5rem;
            }

            .curriculum-card {
                border-radius: 0.875rem !important;
                margin-bottom: 1.5rem !important;
            }

            .curriculum-card .card-body {
                padding: 1.25rem !important;
            }

            .curriculum-card .card-header {
                padding: 1rem !important;
            }

            .card-header > div {
                flex-direction: row !important;
            }

            .card-header i:last-child {
                position: relative !important;
                top: auto !important;
                right: auto !important;
                margin-left: auto;
            }

            /* Form Layout Mobile */
            .row.g-4.align-items-end,
            .row.g-3.align-items-end {
                flex-direction: column;
                gap: 1rem !important;
            }

            .col-md-6, .col-md-6.col-lg-6,
            .col-lg-8, .col-lg-4 {
                flex-basis: 100% !important;
                max-width: 100% !important;
            }

            /* Button Groups Mobile */
            .btn-group {
                gap: 0.5rem;
            }

            .btn-group .btn {
                flex: 1;
                min-width: auto;
            }

            /* Input Groups */
            .input-group {
                max-width: 100% !important;
            }

            /* Table Responsive */
            .table-responsive {
                border-radius: 0.625rem;
                overflow: hidden;
            }

            .curriculum-table thead {
                display: none;
            }

            .curriculum-table tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: 0.625rem;
                margin-bottom: 0.875rem;
                padding: 1rem;
                background-color: #f9f9f9;
            }

            .curriculum-table tbody tr:hover {
                background-color: var(--maroon-light) !important;
            }

            .curriculum-table tbody td {
                display: grid;
                grid-template-columns: 120px 1fr;
                gap: 0.5rem;
                align-items: center;
                padding: 0.5rem 0 !important;
                border: none;
            }

            .curriculum-table tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--maroon);
                font-size: 0.85rem;
            }

            #subjectsTable tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: 0.625rem;
                margin-bottom: 0.75rem;
                padding: 0.875rem;
                background-color: #f9f9f9;
            }

            #subjectsTable tbody tr:hover {
                background-color: #f9f9f9 !important;
            }

            #subjectsTable tbody tr.subject-assigned {
                opacity: 0.6;
                background-color: #efefef !important;
            }

            #subjectsTable tbody td {
                display: grid;
                grid-template-columns: 100px 1fr;
                gap: 0.4rem;
                align-items: center;
                padding: 0.35rem 0 !important;
                border: none;
                font-size: 0.9rem;
            }

            #subjectsTable tbody td:first-child {
                grid-template-columns: auto;
            }

            #subjectsTable tbody td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--maroon);
                font-size: 0.8rem;
            }

            /* Hide data labels for first column */
            #subjectsTable tbody td:first-child::before {
                content: '';
            }

            /* Tabs Mobile */
            .nav-pills {
                flex-wrap: wrap;
                gap: 0.5rem !important;
            }

            .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .accordion-button {
                padding: 1rem 1.25rem !important;
                font-size: 0.95rem;
            }

            .step-badge,
            .year-badge {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }

            /* Modal Mobile */
            .modal-dialog {
                margin: 1rem;
            }

            /* Spacing Adjustments */
            .mb-4 {
                margin-bottom: 1rem !important;
            }

            .mb-5 {
                margin-bottom: 1.5rem !important;
            }

            .gap-4 {
                gap: 1rem !important;
            }

            /* Search Bar */
            .d-flex.flex-column.flex-md-row {
                flex-direction: column !important;
            }

            /* Assignment Details Card Full Width */
            .card.border-maroon {
                margin-top: 1.5rem;
            }

            .badge {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .curriculum-card {
                border-radius: 0.75rem !important;
            }

            .curriculum-card .card-body {
                padding: 1rem !important;
            }

            .curriculum-card .card-header {
                padding: 0.875rem !important;
            }

            .alert {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .badge {
                font-size: 0.75rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .form-select-md,
            .form-control-md {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.55rem 1rem;
                font-size: 0.85rem;
            }

            .btn-sm {
                padding: 0.35rem 0.65rem;
                font-size: 0.75rem;
            }

            .step-badge,
            .year-badge {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
            }

            .accordion-button {
                padding: 0.875rem 1rem !important;
                font-size: 0.9rem;
            }

            #subjectsTable tbody td {
                grid-template-columns: 80px 1fr;
            }

            .curriculum-table tbody td {
                grid-template-columns: 90px 1fr;
            }

            /* Full width buttons on mobile */
            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                width: 100%;
            }

            /* Text adaptation */
            .d-none.d-sm-inline {
                display: none !important;
            }

            .d-sm-none {
                display: block !important;
            }

            /* Icon Text Helper */
            .text-nowrap {
                white-space: nowrap;
            }
        }

        @media (max-width: 480px) {
            .card-header > div > div {
                flex: 1 1 100%;
            }

            .badge {
                padding: 0.3rem 0.5rem;
                font-size: 0.7rem;
            }

            .table th,
            .table td {
                padding: 0.5rem !important;
            }

            #subjectsTable tbody td {
                grid-template-columns: 70px 1fr;
                gap: 0.25rem;
            }
        }

        /* ==== ANIMATIONS ==== */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .curriculum-card {
            animation: fadeIn 400ms ease 1;
        }

        /* Loading spinner */
        .spinner-border {
            animation: spinner-border 0.75s linear infinite;
        }

        /* Smooth tab transitions */
        .tab-pane {
            animation: fadeIn 250ms ease;
        }

        /* ==== PRINT STYLES ==== */
        @media print {
            .btn, .alert, .btn-close, .nav-link, .form-check,
            .input-group, .btn-group, .form-select {
                display: none !important;
            }

            .curriculum-card {
                box-shadow: none;
                border: 1px solid #e9ecef;
                page-break-inside: avoid;
            }

            .curriculum-card .card-body {
                padding: 1.25rem !important;
            }

            .table {
                font-size: 9pt;
            }

            .card-header {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .accordion-item {
                border: 1px solid #e9ecef;
                page-break-inside: avoid;
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
            const selectedCount = document.getElementById('selectedCount');
            const selectedLabel = document.getElementById('selectedLabel');
            const curriculumLoading = document.getElementById('curriculumLoading');
            const assignmentForm = document.getElementById('assignmentForm');

            // Utility: Show loading indicator with fade-in
            const showLoading = () => {
                if (curriculumLoading) {
                    curriculumLoading.classList.remove('d-none');
                    curriculumLoading.style.animation = 'fadeIn 200ms ease';
                }
            };

            // Utility: Hide loading indicator
            const hideLoading = () => {
                if (curriculumLoading) {
                    curriculumLoading.classList.add('d-none');
                }
            };

            // Utility: Show success message with animation
            const showSuccessMessage = (message) => {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.style.animation = 'fadeIn 300ms ease';
                alert.innerHTML = `
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <strong>Success!</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                const container = document.querySelector('#curriculumPage');
                if (container) {
                    container.insertBefore(alert, container.firstChild);
                    setTimeout(() => {
                        alert.style.animation = 'fadeOut 300ms ease';
                        setTimeout(() => alert?.remove(), 300);
                    }, 4000);
                }
            };

            const currentKey = () => `${yearLevel.value}|${semester.value}`;

            // Refresh checkbox states based on selection
            const refreshCheckboxStates = () => {
                const key = currentKey();
                checkboxes.forEach((cb) => {
                    const row = cb.closest('tr');
                    const subjectKeyList = assignedMatrix[cb.dataset.subjectId] || [];
                    const alreadyAssigned = subjectKeyList.includes(key);
                    cb.disabled = !programSelect.value || alreadyAssigned;

                    if (alreadyAssigned) {
                        cb.checked = false;
                        row?.classList.add('subject-assigned');
                    } else {
                        row?.classList.remove('subject-assigned');
                    }
                });

                selectAll.checked = false;
                syncAssignButtonState();
            };

            // Sync assign button state
            const syncAssignButtonState = () => {
                const anyChecked = checkboxes.some((cb) => cb.checked && !cb.disabled);
                assignBtn.disabled = !programSelect.value || !anyChecked;

                // Update selected count with smooth transition
                const count = checkboxes.filter(cb => cb.checked).length;
                selectedCount.textContent = count;
                selectedLabel.textContent = count === 1 ? 'subject' : 'subjects';
            };

            // Update subject count in badge
            const updateSubjectCount = () => {
                const visibleRows = Array.from(checkboxes).filter(
                    (cb) => !cb.closest('tr')?.classList.contains('d-none')
                );
                subjectCountBadge.textContent = `${visibleRows.length} subject${visibleRows.length === 1 ? '' : 's'}`;
            };

            // Select All functionality
            selectAll?.addEventListener('change', (event) => {
                checkboxes.forEach((cb) => {
                    const row = cb.closest('tr');
                    if (!cb.disabled && !row?.classList.contains('d-none')) {
                        cb.checked = event.target.checked;
                    }
                });
                syncAssignButtonState();
            });

            // Checkbox change listeners
            checkboxes.forEach((cb) => {
                cb.addEventListener('change', syncAssignButtonState);
            });

            // Program selection change
            programSelect?.addEventListener('change', (event) => {
                programIdInput.value = event.target.value;
                refreshCheckboxStates();

                // Show loading when program changes
                if (event.target.value) {
                    showLoading();
                    setTimeout(hideLoading, 500);
                }
            });

            // Year level change
            yearLevel?.addEventListener('change', refreshCheckboxStates);

            // Semester change
            semester?.addEventListener('change', refreshCheckboxStates);

            // Search functionality with debounce
            let searchTimeout;
            subjectSearch?.addEventListener('input', (event) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
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
                }, 150);
            });

            // Clear filters
            clearFiltersBtn?.addEventListener('click', () => {
                if (programSelect) programSelect.value = '';
                if (academicYear) academicYear.value = '';
                showLoading();
                document.getElementById('programSelectForm')?.submit();
            });

            // Assignment form submission
            assignmentForm?.addEventListener('submit', (event) => {
                const selectedSubjects = checkboxes.filter(cb => cb.checked).length;

                if (selectedSubjects === 0) {
                    event.preventDefault();
                    showSuccessMessage('Please select at least one subject to assign.');
                    return;
                }

                showLoading();
            });

            // Remove subject modal handler
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

            // Initialize
            refreshCheckboxStates();
            updateSubjectCount();
            hideLoading();

            // Add smooth scroll behavior to page
            document.addEventListener('DOMContentLoaded', () => {
                // Ensure buttons have proper styling
                const setupButtons = () => {
                    document.querySelectorAll('.btn').forEach(btn => {
                        btn.style.transition = 'all 150ms ease';
                    });
                };
                setupButtons();
            });
        })();
    </script>
@endpush
