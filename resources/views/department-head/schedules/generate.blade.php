@extends('layouts.app')

@section('page-title', 'Generate Schedule')

@section('content')
<div class="container-fluid py-4">



    <!-- Page Header -->
    <div class="mb-4">
        <p class="text-muted"><i class="fas fa-cog me-2"></i> Generate optimized schedules for programs in your department</p>
    </div>

    <!-- Two Column Layout: Configuration + Preview -->
    <div class="row g-4 mb-4">
        <!-- Left: Schedule Configuration -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-maroon text-white border-0 rounded-top-3 py-3">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2 text-white">
                        <i class="fas fa-cog"></i>
                        <span>Schedule Configuration</span>
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form id="scheduleConfigForm">
                        @csrf

                        <p class="text-muted mb-4">Configure scheduling parameters before generating class schedules.</p>

                        <!-- Program Selection -->
                        <div class="mb-3">
                            <label for="programSelect" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-university text-maroon"></i>
                                <span>Program</span>
                                <span class="text-danger ms-auto">*</span>
                            </label>
                            <select class="form-select border-1 focus-maroon" id="programSelect" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Academic Year -->
                        <div class="mb-3">
                            <label for="academicYear" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-calendar text-maroon"></i>
                                <span>Academic Year</span>
                                <span class="text-danger ms-auto">*</span>
                            </label>
                            <select class="form-select border-1 focus-maroon" id="academicYear" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (isset($defaultAcademicYearId) && (int) $defaultAcademicYearId === (int) $year->id) ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Semester -->
                        <div class="mb-3">
                            <label for="semester" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-calendar-days text-maroon"></i>
                                <span>Semester</span>
                                <span class="text-danger ms-auto">*</span>
                            </label>
                            <select class="form-select border-1 focus-maroon" id="semester" name="semester" required>
                                <option value="">-- Select Semester --</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->name }}">{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Level -->
                        <div class="mb-3">
                            <label for="yearLevel" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-layer-group text-maroon"></i>
                                <span>Year Level</span>
                                <span class="text-danger ms-auto">*</span>
                            </label>
                            <select class="form-select border-1 focus-maroon" id="yearLevel" name="year_level" required>
                                <option value="">-- Select Year Level --</option>
                                @foreach ($yearLevelOptions as $yearLevelOption)
                                    <option value="{{ $yearLevelOption['value'] }}">{{ $yearLevelOption['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Number of Blocks -->
                        <div class="mb-4">
                            <label for="numberOfBlocks" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-object-group text-maroon"></i>
                                <span>Number of Blocks</span>
                                <span class="text-danger ms-auto">*</span>
                            </label>
                            <input type="number" class="form-control border-1 focus-maroon" id="numberOfBlocks" name="number_of_blocks"
                                   placeholder="Enter number of blocks" min="1" required>
                            <small class="form-text text-muted d-block mt-2">This determines how many class sections will be generated for the selected year level.</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('department-head.schedules.index') }}" class="btn btn-outline-secondary fw-semibold py-2 rounded-2 flex-fill text-center">
                                Cancel
                            </a>
                            <button type="button" class="btn btn-maroon fw-semibold py-2 rounded-2 generate-btn flex-fill"
                                    id="generateScheduleBtn" 
                                    onclick="showConfirmationModal()">
                                Generate Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Schedule Preview -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3" id="previewSection" style="display: none;">
                <div class="card-header bg-light border-0 rounded-top-3 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-calendar-alt me-2 text-maroon"></i> Schedule Preview
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="switchView('grid')">
                                <i class="fas fa-th me-1"></i> Grid
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="switchView('table')">
                                <i class="fas fa-list me-1"></i> Table
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="generationProgressPanel" class="mb-4 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-semibold">Generation Progress</h6>
                            <span id="generationProgressText" class="small text-muted">Starting...</span>
                        </div>
                        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                            <div id="generationProgressBar" class="progress-bar bg-maroon" style="width: 0%">0%</div>
                        </div>
                    </div>

                    <div id="generatedSchedulesPanel" class="mb-4 d-none">
                        <h6 class="fw-semibold mb-2">Generated Blocks</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Block</th>
                                        <th>Schedule ID</th>
                                        <th>Fitness</th>
                                        <th>Overloaded Faculty</th>
                                        <th>Audit</th>
                                    </tr>
                                </thead>
                                <tbody id="generatedSchedulesBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Grid View: Weekly Timetable -->
                    <div id="gridViewContainer">
                        <div class="table-responsive">
                            <table class="table table-bordered schedule-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">Time</th>
                                        <th>Monday</th>
                                        <th>Tuesday</th>
                                        <th>Wednesday</th>
                                        <th>Thursday</th>
                                        <th>Friday</th>
                                        <th>Saturday</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'] as $time)
                                    <tr>
                                        <td class="fw-semibold text-center small">{{ $time }}</td>
                                        @for ($day = 0; $day < 6; $day++)
                                        <td class="schedule-slot" data-day="{{ $day }}" data-time="{{ $time }}">
                                            <!-- Schedule items will be dynamically inserted -->
                                        </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        <div class="p-3 border-top bg-light rounded-bottom-2 mt-3">
                            <div class="row g-3 small">
                                <div class="col-auto">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-2" style="width: 24px; height: 24px; background-color: #007bff;"></span>
                                        <span class="fw-semibold">Lecture</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-2" style="width: 24px; height: 24px; background-color: #28a745;"></span>
                                        <span class="fw-semibold">Laboratory</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View: List Format -->
                    <div id="tableViewContainer" class="d-none p-4">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Instructor</th>
                                        <th>Room</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody id="tableViewBody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox me-2"></i> No schedule generated yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Export and Action Buttons -->
                <div class="card-footer bg-light border-top rounded-bottom-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold rounded-2" 
                                    id="exportPdfBtn" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold rounded-2" 
                                    id="exportCsvBtn" onclick="exportToCSV()">
                                <i class="fas fa-file-csv me-1"></i> CSV
                            </button>
                        </div>
                        <div class="col ms-auto">
                            <button type="button" class="btn btn-sm btn-maroon fw-semibold px-4 rounded-2" 
                                    id="saveScheduleBtn"
                                    onclick="saveGeneratedSchedule()">
                                <i class="fas fa-check-circle me-1"></i> Save Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-question-circle me-2"></i> Confirm Schedule Generation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to generate a schedule with the following parameters:</p>
                <ul class="list-unstyled mb-0">
                    <li><strong>Program:</strong> <span id="confirmProgram">-</span></li>
                    <li><strong>Academic Year:</strong> <span id="confirmAcademicYear">-</span></li>
                    <li><strong>Semester:</strong> <span id="confirmSemester">-</span></li>
                    <li><strong>Year Level:</strong> <span id="confirmYearLevel">-</span></li>
                    <li><strong>Number of Blocks:</strong> <span id="confirmNumberOfBlocks">-</span></li>
                </ul>
                <p class="text-muted small mt-3 mb-0">
                    <i class="fas fa-info-circle me-1"></i> Generation may take several seconds depending on complexity.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon" onclick="executeGeneration()">
                    <i class="fas fa-check me-2"></i> Confirm & Generate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div id="successToast" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11; display: none;">
    <div class="toast show" role="alert">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" onclick="this.closest('.toast').parentElement.style.display='none'"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Action completed successfully
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root {
        --maroon: #7b0000;
        --maroon-dark: #5a0000;
        --maroon-light: rgba(123, 0, 0, 0.1);
    }

    .text-maroon {
        color: var(--maroon) !important;
    }

    .bg-maroon {
        background-color: var(--maroon) !important;
    }

    .btn-maroon {
        background-color: var(--maroon);
        border-color: var(--maroon);
        color: white;
    }

    .btn-maroon:hover {
        background-color: var(--maroon-dark);
        border-color: var(--maroon-dark);
        color: white;
    }

    .focus-maroon:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.25rem var(--maroon-light);
    }

    .schedule-table {
        border-collapse: collapse;
    }

    .schedule-table td, .schedule-table th {
        border: 1px solid #dee2e6;
    }

    .schedule-slot {
        min-height: 70px;
        padding: 4px;
        font-size: 0.7rem;
    }

    .schedule-item {
        background-color: #007bff;
        color: white;
        padding: 4px;
        margin-bottom: 2px;
        border-radius: 3px;
        font-size: 0.7rem;
    }

    .schedule-item.lab {
        background-color: #28a745;
    }
</style>
@endpush

@push('scripts')
<script>
    let isRunning = false;
    let generationProgressTimer = null;

    async function loadSemestersByAcademicYear(academicYearId, selectedSemester = '') {
        const semesterSelect = document.getElementById('semester');

        semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';

        try {
            let url = '{{ route('api.semesters.index') }}';
            if (academicYearId) {
                url += `?academic_year_id=${encodeURIComponent(academicYearId)}`;
            }

            let response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            let payload = await response.json();
            let semesters = payload.data || [];

            // Fallback: if selected academic year has no semesters, load from all semesters.
            if (academicYearId && semesters.length === 0) {
                response = await fetch('{{ route('api.semesters.index') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                payload = await response.json();
                semesters = payload.data || [];
            }

            // De-duplicate by semester name when loading all records.
            const seenNames = new Set();
            semesters.forEach((semester) => {
                if (!semester?.name || seenNames.has(semester.name)) {
                    return;
                }

                seenNames.add(semester.name);
                const option = document.createElement('option');
                option.value = semester.name;
                option.textContent = semester.name;
                if (selectedSemester && selectedSemester === semester.name) {
                    option.selected = true;
                }
                semesterSelect.appendChild(option);
            });
        } catch (error) {
            showToast('Unable to load semesters from the database.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const academicYearSelect = document.getElementById('academicYear');
        const semesterSelect = document.getElementById('semester');

        // Load semesters from DB on first load for preselected academic year.
        if (academicYearSelect.value) {
            loadSemestersByAcademicYear(academicYearSelect.value, semesterSelect.value);
        }

        academicYearSelect.addEventListener('change', (event) => {
            loadSemestersByAcademicYear(event.target.value);
        });
    });

    // Show Confirmation Modal
    function showConfirmationModal() {
        const form = document.getElementById('scheduleConfigForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Populate confirmation details
        const program = document.getElementById('programSelect');
        const academicYear = document.getElementById('academicYear');
        const semester = document.getElementById('semester');
        const yearLevel = document.getElementById('yearLevel');
        const numberOfBlocks = document.getElementById('numberOfBlocks');

        document.getElementById('confirmProgram').textContent = program.options[program.selectedIndex].text;
        document.getElementById('confirmAcademicYear').textContent = academicYear.options[academicYear.selectedIndex].text;
        document.getElementById('confirmSemester').textContent = semester.options[semester.selectedIndex].text;
        document.getElementById('confirmYearLevel').textContent = yearLevel.options[yearLevel.selectedIndex].text;
        document.getElementById('confirmNumberOfBlocks').textContent = numberOfBlocks.value;

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
    }

    // Execute Generation
    function executeGeneration() {
        isRunning = true;
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        modal.hide();

        // Show preview section
        document.getElementById('previewSection').style.display = 'block';
        document.getElementById('generationProgressPanel').classList.remove('d-none');
        updateProgress(5, 'Preparing data...');
        startLiveProgressAnimation();

        // Disable form inputs
        disableFormInputs(true);

        // Call the backend API
        const formData = new FormData(document.getElementById('scheduleConfigForm'));
        
        fetch('{{ route("department-head.schedules.executeGeneration") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Schedule generated successfully!');
                renderGeneratedSchedules(data.data?.generated_schedules || []);
                const configurationId = data.data?.configuration_id;
                if (configurationId) {
                    fetchGenerationProgress(configurationId);
                } else {
                    updateProgress(100, 'Completed');
                }
                scrollToPreview();
            } else {
                showToast('Generation failed: ' + (data.message || 'Unknown error'), 'error');
                updateProgress(100, 'Failed');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
            updateProgress(100, 'Failed');
        })
        .finally(() => {
            isRunning = false;
            disableFormInputs(false);
            if (generationProgressTimer) {
                clearInterval(generationProgressTimer);
                generationProgressTimer = null;
            }
        });
    }

    function updateProgress(percent, label) {
        const bar = document.getElementById('generationProgressBar');
        const text = document.getElementById('generationProgressText');

        bar.style.width = `${percent}%`;
        bar.textContent = `${percent}%`;
        text.textContent = label;
    }

    function fetchGenerationProgress(configurationId) {
        const progressUrl = '{{ route('department-head.schedules.generation-progress', ['configuration' => '__CONFIG__']) }}'
            .replace('__CONFIG__', configurationId);

        fetch(progressUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    return;
                }

                const data = payload.data || {};
                const percent = data.progress_percent ?? 100;
                updateProgress(percent, `${data.generated_blocks ?? 0} / ${data.total_blocks ?? 0} block(s) generated`);
            })
            .catch(() => {
                updateProgress(100, 'Completed');
            });
    }

    function startLiveProgressAnimation() {
        let progress = 5;

        if (generationProgressTimer) {
            clearInterval(generationProgressTimer);
        }

        generationProgressTimer = setInterval(() => {
            if (!isRunning) {
                clearInterval(generationProgressTimer);
                generationProgressTimer = null;
                return;
            }

            if (progress < 85) {
                progress += 3;
                updateProgress(progress, 'Optimizing schedule with Genetic Algorithm...');
            }
        }, 500);
    }

    function renderGeneratedSchedules(schedules) {
        const panel = document.getElementById('generatedSchedulesPanel');
        const body = document.getElementById('generatedSchedulesBody');

        if (!Array.isArray(schedules) || schedules.length === 0) {
            panel.classList.add('d-none');
            body.innerHTML = '';
            return;
        }

        body.innerHTML = schedules.map((entry) => {
            const overloadedCount = Array.isArray(entry.overloaded_faculty) ? entry.overloaded_faculty.length : 0;
            return `
                <tr>
                    <td>${entry.block ?? '-'}</td>
                    <td>${entry.schedule_id ?? '-'}</td>
                    <td>${Number(entry.fitness_score ?? 0).toFixed(2)}</td>
                    <td>${overloadedCount}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openScheduleAudit(${entry.schedule_id})">
                            View Audit
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        panel.classList.remove('d-none');
    }

    function openScheduleAudit(scheduleId) {
        const auditUrl = '{{ route('department-head.schedules.audit', ['schedule' => '__SCHEDULE__']) }}'
            .replace('__SCHEDULE__', scheduleId);

        fetch(auditUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    showToast(payload.message || 'Failed to load audit report.', 'error');
                    return;
                }

                const hard = payload.data?.hard_conflicts?.total ?? 0;
                const overloaded = (payload.data?.faculty_workloads || []).filter((row) => row.status === 'Overloaded').length;
                showToast(`Audit loaded: hard conflicts = ${hard}, overloaded faculty = ${overloaded}`);
            })
            .catch(() => {
                showToast('Failed to load audit report.', 'error');
            });
    }

    // Disable/Enable Form Inputs
    function disableFormInputs(disable) {
        const inputs = document.getElementById('scheduleConfigForm').querySelectorAll('input, select, button');
        inputs.forEach(input => {
            if (input.id !== 'generateScheduleBtn') {
                input.disabled = disable;
            }
        });
        document.getElementById('generateScheduleBtn').disabled = disable;
    }

    // Switch View
    function switchView(viewType) {
        if (viewType === 'grid') {
            document.getElementById('gridViewContainer').classList.remove('d-none');
            document.getElementById('tableViewContainer').classList.add('d-none');
        } else {
            document.getElementById('gridViewContainer').classList.add('d-none');
            document.getElementById('tableViewContainer').classList.remove('d-none');
        }
    }

    // Export to PDF
    function exportToPDF() {
        showToast('Exporting to PDF... (Feature coming soon)');
    }

    // Export to CSV
    function exportToCSV() {
        showToast('Exporting to CSV... (Feature coming soon)');
    }

    // Save Generated Schedule
    function saveGeneratedSchedule() {
        if (confirm('Are you sure you want to save this generated schedule?')) {
            showToast('Saving schedule...');
            // TODO: Implement save functionality
        }
    }

    // Show Toast
    function showToast(message, type = 'success') {
        document.getElementById('toastMessage').textContent = message;
        const toast = document.getElementById('successToast');
        const header = toast.querySelector('.toast-header');
        
        if (type === 'error') {
            header.classList.remove('bg-success');
            header.classList.add('bg-danger');
        } else {
            header.classList.remove('bg-danger');
            header.classList.add('bg-success');
        }
        
        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // Scroll to Preview
    function scrollToPreview() {
        document.getElementById('previewSection').scrollIntoView({behavior: 'smooth'});
    }
</script>
@endpush
