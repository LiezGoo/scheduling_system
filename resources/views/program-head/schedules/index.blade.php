@extends('layouts.app')

@section('page-title', 'Generate Schedule')

@section('content')
<div class="container-fluid py-4">

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 border-0" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                                    <option value="{{ $year->id }}" {{ old('academic_year_id', request('academic_year_id')) == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
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
                                    <option value="{{ $semester }}">{{ $semester }}</option>
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
                                @foreach ($yearLevels as $level)
                                    <option value="{{ $level }}">{{ $level }}{{ $level == 1 ? 'st' : ($level == 2 ? 'nd' : ($level == 3 ? 'rd' : 'th')) }} Year</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Block/Section -->
                        <div class="mb-4">
                            <label for="blockSection" class="form-label fw-semibold mb-2 text-dark d-flex align-items-center gap-2">
                                <i class="fas fa-object-group text-maroon"></i>
                                <span>Block/Section</span>
                            </label>
                            <input type="text" class="form-control border-1 focus-maroon" id="blockSection" name="block" 
                                   placeholder="e.g., Block 1" maxlength="10">
                            <small class="form-text text-muted d-block mt-2">Leave blank to generate for all blocks</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-maroon fw-semibold py-2 rounded-2 generate-btn w-100" 
                                    id="generateScheduleBtn" 
                                    onclick="generateSchedule()">
                                <i class="fas fa-sparkles me-2"></i>
                                <span>Generate Schedule</span>
                            </button>
                            <a href="{{ route('program-head.schedules.index') }}" class="btn btn-outline-secondary fw-semibold py-2 rounded-2">
                                <i class="fas fa-arrow-left me-2"></i>
                                <span>Back to Schedules</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Schedule Preview Placeholder -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-maroon text-white border-0 rounded-top-3 py-3">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2 text-white">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule Preview</span>
                    </h5>
                </div>
                <div class="card-body p-5 d-flex align-items-center justify-content-center bg-light">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-calendar-week text-muted opacity-50" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-muted fw-semibold mb-2">No Schedule Generated Yet</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH: Generated Schedule Result -->
    <div class="row mt-4" id="previewSection" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-maroon text-white border-0 rounded-top-3 py-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Weekly Schedule</span>
                            </h6>
                        </div>
                        <div class="col ms-auto">
                            <!-- View Toggle -->
                            <div class="btn-group btn-group-sm" role="group" id="viewToggle">
                                <input type="radio" class="btn-check" name="viewType" id="gridView" 
                                       value="grid" checked onchange="switchView('grid')">
                                <label class="btn btn-outline-light" for="gridView">
                                    <i class="fas fa-th me-1"></i> Grid
                                </label>

                                <input type="radio" class="btn-check" name="viewType" id="tableView" 
                                       value="table" onchange="switchView('table')">
                                <label class="btn btn-outline-light" for="tableView">
                                    <i class="fas fa-list me-1"></i> Table
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Grid View: Weekly Timetable -->
                    <div id="gridViewContainer" class="schedule-grid-container p-4">
                        <div class="table-responsive rounded-2" style="overflow-x: auto;">
                            <table class="table mb-0 schedule-table">
                                <thead class="table-light border-bottom">
                                    <tr>
                                        <th style="width: 90px; text-align: center;" class="fw-semibold small bg-light">Time</th>
                                        <th style="text-align: center;" class="fw-semibold small">Monday</th>
                                        <th style="text-align: center;" class="fw-semibold small">Tuesday</th>
                                        <th style="text-align: center;" class="fw-semibold small">Wednesday</th>
                                        <th style="text-align: center;" class="fw-semibold small">Thursday</th>
                                        <th style="text-align: center;" class="fw-semibold small">Friday</th>
                                        <th style="text-align: center;" class="fw-semibold small">Saturday</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Time slots: 7 AM to 7 PM -->
                                    @php
                                        $hours = [];
                                        for ($i = 7; $i < 19; $i++) {
                                            $hours[] = sprintf("%02d:00", $i);
                                        }
                                    @endphp

                                    @foreach($hours as $hour)
                                    <tr style="height: 85px;">
                                        <td style="text-align: center; vertical-align: top; font-weight: 600; color: #660000; background-color: #f8f9fa; border-right: 2px solid #dee2e6;">
                                            <small>{{ $hour }}</small>
                                        </td>
                                        @for($day = 0; $day < 6; $day++)
                                        <td style="border: 1px solid #e9ecef; vertical-align: top; padding: 6px;">
                                            <div class="schedule-slot" data-day="{{ $day }}" data-time="{{ $hour }}">
                                                <!-- Schedule items will be dynamically inserted -->
                                            </div>
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
                                <div class="col-auto">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-2" style="width: 24px; height: 24px; background-color: #dc3545;"></span>
                                        <span class="fw-semibold">Conflict</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-2" style="width: 24px; height: 24px; background-color: #6c757d;"></span>
                                        <span class="fw-semibold">Reserved/Break</span>
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
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tableViewBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
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
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold rounded-2" 
                                    id="printBtn" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
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
                <p class="mb-3">You're about to generate a new schedule using the Genetic Algorithm.</p>
                
                <div class="alert alert-info" role="alert">
                    <small>
                        <strong>Configuration Summary:</strong>
                        <ul class="mb-0 mt-2" style="font-size: 0.85rem;">
                            <li id="summaryAcademicYear">Academic Year: --</li>
                            <li id="summarySemester">Semester: --</li>
                            <li id="summaryYearLevel">Year Level: --</li>
                            <li id="summaryBlockSection">Block/Section: --</li>
                        </ul>
                    </small>
                </div>

                <div class="alert alert-warning" role="alert">
                    <small>
                        <i class="fas fa-hourglass-half me-1"></i>
                        <strong>Note:</strong> This operation may take a few minutes. Please don't close this page.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon fw-bold" 
                        onclick="executeGeneration()">
                    <i class="fas fa-play-circle me-1"></i> Proceed with Generation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-history me-2"></i> Generation History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Year/Semester</th>
                                <th>Best Fitness</th>
                                <th>Conflicts</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox me-2"></i> No generation history yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div id="successToast" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999; display: none;">
    <div class="toast show border-0 shadow bg-success text-white" role="alert">
        <div class="toast-body">
            <i class="fas fa-check-circle me-2"></i>
            <strong id="toastMessage">Schedule generated successfully!</strong>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Root Color Variables */
    :root {
        --maroon: #7B0000;
        --maroon-dark: #660000;
        --maroon-light: rgba(123, 0, 0, 0.1);
    }

    /* Form Controls - Focus Styling */
    .form-select.focus-maroon:focus,
    .form-control.focus-maroon:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.25rem var(--maroon-light);
    }

    .form-select.focus-maroon,
    .form-control.focus-maroon {
        border: 1px solid #dee2e6;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    /* Form Labels Styling */
    .form-label {
        font-size: 0.95rem;
        color: #212529;
        margin-bottom: 0.5rem;
    }

    .form-label i {
        font-size: 1.1rem;
    }

    /* Generate Button Styling */
    .generate-btn {
        background-color: var(--maroon);
        border-color: var(--maroon);
        color: white;
        font-size: 1rem;
        padding: 0.625rem 1rem;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .generate-btn:hover {
        background-color: var(--maroon-dark);
        border-color: var(--maroon-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(123, 0, 0, 0.3);
        color: white;
    }

    .generate-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(123, 0, 0, 0.3);
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
    }

    /* Card Header Maroon */
    .bg-maroon {
        background-color: var(--maroon) !important;
    }

    /* Card Hover Effects */
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    }

    /* Text Color */
    .text-maroon {
        color: var(--maroon) !important;
    }

    /* Schedule Grid Styling */
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
        border-left: 3px solid #0056b3;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .schedule-item:hover {
        transform: scale(0.98);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .schedule-item.lecture {
        background-color: #007bff;
        border-left-color: #0056b3;
    }

    .schedule-item.lab {
        background-color: #28a745;
        border-left-color: #1e7e34;
    }

    .schedule-item.conflict {
        background-color: #dc3545;
        border-left-color: #a71d2a;
    }

    .schedule-item.reserved {
        background-color: #6c757d;
        border-left-color: #545b62;
    }

    /* Progress Bar */
    .progress {
        border-radius: 6px;
    }

    .progress-bar {
        transition: width 0.3s ease;
        border-radius: 6px;
    }

    /* Badges */
    .badge {
        border-radius: 4px;
    }

    /* Print Styles */
    @media print {
        .btn, .alert-warning, .alert-info, .card-footer, .card-header.bg-maroon {
            display: none !important;
        }

        .schedule-table {
            font-size: 9pt;
        }
    }

    /* Responsive */
    @media (max-width: 991px) {
        .card-body {
            padding: 1.5rem !important;
        }
    }

    @media (max-width: 768px) {
        .schedule-table {
            font-size: 0.7rem;
        }

        .schedule-slot {
            min-height: 60px;
            font-size: 0.65rem;
        }

        .card-body {
            padding: 1rem !important;
        }

        .form-label {
            font-size: 0.9rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => {
            new bootstrap.Tooltip(el);
        });
    });

    // State Management
    let isRunning = false;
    let currentGeneration = 0;
    let totalGenerations = 0;
    let progressInterval = null;

    // Generate Schedule
    function generateSchedule() {
        // Validate form
        const form = document.getElementById('scheduleConfigForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Show confirmation modal
        showConfirmationModal();
    }

    // Show Confirmation Modal
    function showConfirmationModal() {
        // Populate summary
        const academicYear = document.getElementById('academicYear');
        const semester = document.getElementById('semester');
        const yearLevel = document.getElementById('yearLevel');
        const blockSection = document.getElementById('blockSection');

        document.getElementById('summaryAcademicYear').textContent = 
            'Academic Year: ' + (academicYear.options[academicYear.selectedIndex]?.text || '--');
        document.getElementById('summarySemester').textContent = 
            'Semester: ' + (semester.options[semester.selectedIndex]?.text || '--');
        document.getElementById('summaryYearLevel').textContent = 
            'Year Level: ' + (yearLevel.options[yearLevel.selectedIndex]?.text || '--');
        document.getElementById('summaryBlockSection').textContent = 
            'Block/Section: ' + (blockSection.value || 'All blocks');

        // Validate form
        if (!document.getElementById('scheduleConfigForm').checkValidity()) {
            document.getElementById('scheduleConfigForm').reportValidity();
            return;
        }

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

        // Disable form inputs
        disableFormInputs(true);

        // Simulate generation completion
        setTimeout(() => {
            completeGeneration();
        }, 2000);
    }

    // Simulate Generation Progress
    function simulateGeneration() {
        let progress = 0;
        const interval = 500; // Update every 500ms

        progressInterval = setInterval(() => {
            if (!isRunning) {
                clearInterval(progressInterval);
                return;
            }

            progress += Math.random() * 10; // Random progress increment
            if (progress > 100) progress = 100;

            currentGeneration = Math.floor((progress / 100) * totalGenerations);

            // Update UI
            updateProgress(progress);
            updateCurrentGeneration(currentGeneration, totalGenerations);
            updateFitnessScore(80 + Math.random() * 20);
            updateConflictCount(Math.max(0, 5 - Math.floor(progress / 20)));
            updatePenaltyScore(Math.max(0, 100 - progress));

            if (progress >= 100) {
                clearInterval(progressInterval);
                completeGeneration();
            }
        }, interval);
    }

    // Update Progress
    function updateProgress(percentage) {
        const progressBar = document.getElementById('progressBar');
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        document.getElementById('progressPercentage').textContent = Math.floor(percentage) + '%';
    }

    // Update Current Generation
    function updateCurrentGeneration(current, total) {
        document.getElementById('currentGeneration').textContent = `${current} / ${total}`;
    }

    // Update Fitness Score
    function updateFitnessScore(score) {
        document.getElementById('bestFitnessScore').textContent = score.toFixed(2);
    }

    // Update Conflict Count
    function updateConflictCount(count) {
        document.getElementById('conflictCount').textContent = count;
    }

    // Update Penalty Score
    function updatePenaltyScore(score) {
        document.getElementById('penaltyScore').textContent = score.toFixed(0);
    }

    // Update Status Badge
    function updateStatus(status) {
        const badge = document.getElementById('statusBadge');
        badge.classList.remove('bg-secondary', 'bg-primary', 'bg-success', 'bg-danger');

        const statusMap = {
            'Idle': 'bg-secondary',
            'Running': 'bg-primary',
            'Completed': 'bg-success',
            'Failed': 'bg-danger'
        };

        badge.classList.add(statusMap[status] || 'bg-secondary');

        const iconMap = {
            'Idle': 'fa-pause-circle',
            'Running': 'fa-spinner fa-spin',
            'Completed': 'fa-check-circle',
            'Failed': 'fa-exclamation-circle'
        };

        badge.innerHTML = `<i class="fas ${iconMap[status]} me-1"></i> ${status}`;
    }

    // Complete Generation
    function completeGeneration() {
        isRunning = false;
        disableFormInputs(false);

        // Show success toast
        showToast('Schedule generated successfully!');

        // Show preview section
        document.getElementById('previewSection').style.display = 'block';

        // Scroll to preview
        scrollToPreview();
    }

    // Cancel Generation
    function cancelGeneration() {
        isRunning = false;
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        updateStatus('Idle');
        document.getElementById('runningIndicator').classList.add('d-none');
        disableFormInputs(false);
        document.getElementById('stopButton').disabled = true;
        document.getElementById('generateScheduleBtn').disabled = false;
        updateProgress(0);
        updateCurrentGeneration(0, 0);
        showToast('Generation cancelled');
    }

    // Disable/Enable Form Inputs
    function disableFormInputs(disable) {
        const inputs = document.getElementById('scheduleConfigForm').querySelectorAll('input, select, button');
        inputs.forEach(input => {
            if (input.id !== 'stopButton' && input.id !== 'generateScheduleBtn') {
                input.disabled = disable;
            }
        });
    }

    // Reset to Defaults
    function resetToDefaults() {
        document.getElementById('populationSize').value = 50;
        document.getElementById('generations').value = 100;
        document.getElementById('mutationRate').value = 15;
        document.getElementById('crossoverRate').value = 80;
        document.getElementById('eliteSize').value = 5;
    }

    // Get day index
    function getDay(dayName) {
        const days = {Monday: 0, Tuesday: 1, Wednesday: 2, Thursday: 3, Friday: 4, Saturday: 5};
        return days[dayName] || 0;
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
            showToast('Saving schedule... (Feature coming soon)');
            // TODO: Implement save functionality
            // This would send the generated schedule to the server
        }
    }

    // Show Toast
    function showToast(message) {
        document.getElementById('toastMessage').textContent = message;
        const toast = document.getElementById('successToast');
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
