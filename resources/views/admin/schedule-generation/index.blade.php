@extends('layouts.app')

@section('page-title', 'Schedule Generation')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-calendar-check me-2"></i>
                    Generate and manage class schedules for academic programs
                </p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="scheduleFilterForm" novalidate>
                    @csrf
                    <div class="row g-3 align-items-end">
                        <!-- Academic Year -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterAcademicYear" class="form-label">
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterAcademicYear" name="academic_year" required>
                                <option value="">Select Academic Year</option>
                                <option value="2024-2025">2024–2025</option>
                                <option value="2025-2026">2025–2026</option>
                                <option value="2026-2027">2026–2027</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select an academic year.
                            </div>
                        </div>

                        <!-- Semester -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterSemester" class="form-label">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterSemester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a semester.
                            </div>
                        </div>

                        <!-- Department (Disabled for Department Heads) -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterDepartment" class="form-label">
                                Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterDepartment" name="department_id" required>
                                <option value="">Select Department</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a department.
                            </div>
                        </div>

                        <!-- Program -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterProgram" class="form-label">
                                Program <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterProgram" name="program_id" required>
                                <option value="">Select Program</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a program.
                            </div>
                        </div>

                        <!-- Year Level -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterYearLevel" class="form-label">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterYearLevel" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a year level.
                            </div>
                        </div>

                        <!-- Block / Section -->
                        <div class="col-lg-2 col-md-6">
                            <label for="filterBlock" class="form-label">
                                Block / Section <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="filterBlock" name="block_section" required>
                                <option value="">Select Block</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a block / section.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary" id="clearFiltersBtn">
                                    <i class="fa-solid fa-rotate-left me-2"></i>Clear Filters
                                </button>
                                <button type="submit" class="btn btn-maroon" id="generateScheduleBtn" disabled>
                                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedule Grid Section -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Empty State Message -->
                <div id="emptyStateContainer" class="text-center py-5">
                    <div class="mb-3">
                        <i class="fa-solid fa-inbox text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted">No Schedule Generated</h5>
                    <p class="text-muted small">
                        Select all filter options above and click "Generate Schedule" to create a timetable.
                    </p>
                </div>

                <!-- Schedule Table (Hidden by default) -->
                <div id="scheduleTableContainer" style="display: none;">
                    <!-- Schedule Info -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-light border" role="alert">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <small class="d-block text-muted">Academic Year</small>
                                        <strong id="displayAcademicYear">—</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="d-block text-muted">Program</small>
                                        <strong id="displayProgram">—</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="d-block text-muted">Year Level & Section</small>
                                        <strong id="displayYearBlock">—</strong>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <small class="d-block text-muted">Generated</small>
                                        <strong id="displayGeneratedTime">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Timetable -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped schedule-table">
                            <thead>
                                <tr class="table-light">
                                    <th class="bg-light text-center" style="width: 80px;">
                                        <strong>Time</strong>
                                    </th>
                                    <th class="text-center">Monday</th>
                                    <th class="text-center">Tuesday</th>
                                    <th class="text-center">Wednesday</th>
                                    <th class="text-center">Thursday</th>
                                    <th class="text-center">Friday</th>
                                    <th class="text-center">Saturday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Time Slot: 7:00-8:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>7:00–8:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 8:00-9:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>8:00–9:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 9:00-10:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle">
                                        <small><strong>9:00–10:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 10:00-11:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle">
                                        <small><strong>10:00–11:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 11:00-12:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle">
                                        <small><strong>11:00–12:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 12:00-13:00 (Lunch) -->
                                <tr class="table-light">
                                    <td class="bg-light text-center align-middle">
                                        <small><strong>12:00–1:00</strong></small>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                    <td class="schedule-cell text-center text-muted" style="min-height: 80px;">
                                        <em>Break</em>
                                    </td>
                                </tr>

                                <!-- Time Slot: 13:00-14:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>1:00–2:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 14:00-15:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>2:00–3:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 15:00-16:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>3:00–4:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 16:00-17:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>4:00–5:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>

                                <!-- Time Slot: 17:00-18:00 -->
                                <tr>
                                    <td class="bg-light text-center align-middle"><small><strong>5:00–6:00</strong></small>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                    <td class="schedule-cell" style="min-height: 80px;"><span class="text-muted">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-secondary" id="resetScheduleBtn">
                                    <i class="fa-solid fa-redo me-2"></i>Reset Schedule
                                </button>
                                <button type="button" class="btn btn-maroon" id="saveScheduleBtn">
                                    <i class="fa-solid fa-save me-2"></i>Save Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Styles -->
    <style>
        /* Schedule Table Styling */
        .schedule-table {
            margin-bottom: 0;
        }

        .schedule-table thead th {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            font-weight: 600;
            color: #333;
            padding: 1rem 0.5rem;
        }

        .schedule-cell {
            vertical-align: top;
            padding: 0.75rem;
            background-color: #ffffff;
            border-color: #e9ecef;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .schedule-cell:hover {
            background-color: #f8f9fa;
        }

        /* Schedule Cell Content */
        .schedule-cell .class-info {
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .schedule-cell .subject-code {
            font-weight: 600;
            color: #660000;
        }

        .schedule-cell .instructor-name {
            color: #495057;
            font-size: 0.8rem;
        }

        .schedule-cell .room-info {
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Form Styling */
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-label .text-danger {
            margin-left: 2px;
        }

        /* Buttons */
        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: white;
        }

        .btn-maroon:hover {
            background-color: #880000;
            border-color: #880000;
            color: white;
        }

        .btn-maroon:focus {
            background-color: #660000;
            border-color: #660000;
            box-shadow: 0 0 0 0.25rem rgba(102, 0, 0, 0.25);
            color: white;
        }

        .btn-maroon:disabled {
            background-color: #660000;
            border-color: #660000;
            opacity: 0.65;
        }

        /* Empty State */
        #emptyStateContainer {
            color: #6c757d;
        }

        /* Alert Box */
        .alert-light {
            background-color: #f8f9fa;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .schedule-cell {
                min-height: 60px !important;
                padding: 0.5rem;
            }

            .schedule-cell .class-info {
                font-size: 0.75rem;
            }

            .schedule-table thead th {
                padding: 0.75rem 0.3rem;
                font-size: 0.9rem;
            }

            .row.g-3 {
                gap: 1rem !important;
            }
        }

        @media (max-width: 576px) {
            .schedule-table {
                font-size: 0.85rem;
            }

            .schedule-cell {
                min-height: 50px !important;
                padding: 0.3rem;
            }

            .d-flex.gap-2 {
                flex-direction: column;
            }

            .d-flex.gap-2>button {
                width: 100%;
            }

            .alert-light .row {
                font-size: 0.85rem;
            }
        }
    </style>

    <!-- Frontend Logic Script (Placeholder) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to form elements
            const scheduleFilterForm = document.getElementById('scheduleFilterForm');
            const academicYearSelect = document.getElementById('filterAcademicYear');
            const semesterSelect = document.getElementById('filterSemester');
            const departmentSelect = document.getElementById('filterDepartment');
            const programSelect = document.getElementById('filterProgram');
            const yearLevelSelect = document.getElementById('filterYearLevel');
            const blockSelect = document.getElementById('filterBlock');

            const generateScheduleBtn = document.getElementById('generateScheduleBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const saveScheduleBtn = document.getElementById('saveScheduleBtn');
            const resetScheduleBtn = document.getElementById('resetScheduleBtn');

            const emptyStateContainer = document.getElementById('emptyStateContainer');
            const scheduleTableContainer = document.getElementById('scheduleTableContainer');

            // Enable/disable Generate Schedule button based on form validation
            function updateGenerateButtonState() {
                const allFieldsValid = academicYearSelect.value &&
                    semesterSelect.value &&
                    departmentSelect.value &&
                    programSelect.value &&
                    yearLevelSelect.value &&
                    blockSelect.value;

                generateScheduleBtn.disabled = !allFieldsValid;
            }

            // Add listeners to all select fields
            academicYearSelect.addEventListener('change', updateGenerateButtonState);
            semesterSelect.addEventListener('change', updateGenerateButtonState);
            departmentSelect.addEventListener('change', updateGenerateButtonState);
            programSelect.addEventListener('change', updateGenerateButtonState);
            yearLevelSelect.addEventListener('change', updateGenerateButtonState);
            blockSelect.addEventListener('change', updateGenerateButtonState);

            // Generate Schedule button click
            generateScheduleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Validate form
                if (!scheduleFilterForm.checkValidity()) {
                    scheduleFilterForm.classList.add('was-validated');
                    return;
                }

                // Show schedule table, hide empty state
                emptyStateContainer.style.display = 'none';
                scheduleTableContainer.style.display = 'block';

                // Update display values
                document.getElementById('displayAcademicYear').textContent = academicYearSelect.options[
                    academicYearSelect.selectedIndex].text;
                document.getElementById('displayProgram').textContent = programSelect.options[programSelect
                    .selectedIndex].text;
                document.getElementById('displayYearBlock').textContent =
                    yearLevelSelect.options[yearLevelSelect.selectedIndex].text + ' - Section ' +
                    blockSelect.options[blockSelect.selectedIndex].text;
                document.getElementById('displayGeneratedTime').textContent = new Date().toLocaleString();

                // Scroll to schedule table
                scheduleTableContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            // Clear Filters button click
            clearFiltersBtn.addEventListener('click', function() {
                scheduleFilterForm.reset();
                scheduleFilterForm.classList.remove('was-validated');
                emptyStateContainer.style.display = 'block';
                scheduleTableContainer.style.display = 'none';
                updateGenerateButtonState();
            });

            // Reset Schedule button click
            resetScheduleBtn.addEventListener('click', function() {
                // Reset all schedule cells to empty state
                const cells = document.querySelectorAll('.schedule-cell');
                cells.forEach(cell => {
                    if (!cell.textContent.includes('Break')) {
                        cell.innerHTML = '<span class="text-muted">—</span>';
                    }
                });
            });

            // Save Schedule button click
            saveScheduleBtn.addEventListener('click', function() {
                // Placeholder for save functionality
                alert('Schedule saved successfully! (Placeholder UI)');
            });

            // Initialize button state
            updateGenerateButtonState();
        });
    </script>
@endsection
