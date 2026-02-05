@extends('layouts.app')

@section('page-title', 'Student Dashboard')

@section('content')
    <div class="container-fluid py-3 py-md-4">
        <!-- Welcome Header -->
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body py-3 py-md-4">
                        <div
                            class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3">
                            <div>
                                <h4 class="mb-1 h5 h4-md">Welcome, {{ Auth::user()->full_name }}!</h4>
                                <p class="text-muted mb-0 small">View class schedules</p>
                            </div>
                            <span class="badge bg-maroon text-white px-3 py-2">Student</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Filter Section -->
        <div class="card shadow-sm mb-3 mb-md-4">
            <div class="card-header bg-maroon text-white py-2 py-md-3">
                <h5 class="mb-0 h6 h5-md">
                    <i class="fa-solid fa-filter me-2"></i>View Class Schedule
                </h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <form id="scheduleFilterForm" novalidate>
                    @csrf
                    <div class="row g-2 g-md-3">
                        <!-- Academic Year -->
                        <div class="col-12 col-sm-6 col-lg-4 col-xl">
                            <label for="filterAcademicYear" class="form-label small fw-semibold">
                                Academic Year <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm form-select-md-normal" id="filterAcademicYear"
                                name="academic_year" required>
                                <option value="" selected>Select Year</option>
                                <option value="2024-2025">2024–2025</option>
                                <option value="2025-2026">2025–2026</option>
                                <option value="2026-2027">2026–2027</option>
                            </select>
                            <div class="invalid-feedback small">Please select an academic year.</div>
                        </div>

                        <!-- Semester -->
                        <div class="col-12 col-sm-6 col-lg-4 col-xl">
                            <label for="filterSemester" class="form-label small fw-semibold">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm form-select-md-normal" id="filterSemester"
                                name="semester" required>
                                <option value="" selected>Select Semester</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                            </select>
                            <div class="invalid-feedback small">Please select a semester.</div>
                        </div>

                        <!-- Program -->
                        <div class="col-12 col-sm-6 col-lg-4 col-xl">
                            <label for="filterProgram" class="form-label small fw-semibold">
                                Program <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm form-select-md-normal" id="filterProgram"
                                name="program" required>
                                <option value="" selected>Select Program</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSIS">BSIS</option>
                                <option value="BTVTED">BTVTED</option>
                            </select>
                            <div class="invalid-feedback small">Please select a program.</div>
                        </div>

                        <!-- Year Level -->
                        <div class="col-12 col-sm-6 col-lg-4 col-xl">
                            <label for="filterYearLevel" class="form-label small fw-semibold">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm form-select-md-normal" id="filterYearLevel"
                                name="year_level" required>
                                <option value="" selected>Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback small">Please select a year level.</div>
                        </div>

                        <!-- Block / Section -->
                        <div class="col-12 col-sm-6 col-lg-4 col-xl">
                            <label for="filterBlock" class="form-label small fw-semibold">
                                Block / Section <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm form-select-md-normal" id="filterBlock"
                                name="block_section" required>
                                <option value="" selected>Select Block</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                            <div class="invalid-feedback small">Please select a block.</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-3 mt-md-4">
                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm btn-md-normal order-2 order-sm-1"
                                    id="resetFiltersBtn">
                                    <i class="fa-solid fa-rotate-left me-2"></i>Reset Filters
                                </button>
                                <button type="submit" class="btn btn-maroon btn-sm btn-md-normal order-1 order-sm-2"
                                    id="viewScheduleBtn">
                                    <i class="fa-solid fa-eye me-2"></i>View Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedule Display Section -->
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 py-md-3">
                <div
                    class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                    <h5 class="mb-0 h6 h5-md">
                        <i class="fa-solid fa-calendar-week me-2 text-maroon"></i>My Class Schedule
                    </h5>
                    <div id="scheduleInfo" class="text-muted small d-none">
                        <span id="displayScheduleDetails"></span>
                    </div>
                </div>
            </div>
            <div class="card-body p-2 p-md-3 p-lg-4">
                <!-- Empty State -->
                <div id="emptyState" class="text-center py-4 py-md-5">
                    <div class="mb-3">
                        <i class="fa-solid fa-calendar-xmark text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted h6 h5-md">No Schedule to Display</h5>
                    <p class="text-muted small mb-0">
                        Select filters above to view your class schedule.
                    </p>
                </div>

                <!-- Desktop/Tablet: Table View -->
                <div id="scheduleTableView" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover schedule-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center bg-light time-column" style="width: 100px;">
                                        <small class="fw-bold">Time</small>
                                    </th>
                                    <th class="text-center"><small class="fw-bold d-none d-md-inline">Monday</small><small
                                            class="fw-bold d-md-none">Mon</small></th>
                                    <th class="text-center"><small
                                            class="fw-bold d-none d-md-inline">Tuesday</small><small
                                            class="fw-bold d-md-none">Tue</small></th>
                                    <th class="text-center"><small
                                            class="fw-bold d-none d-md-inline">Wednesday</small><small
                                            class="fw-bold d-md-none">Wed</small></th>
                                    <th class="text-center"><small
                                            class="fw-bold d-none d-md-inline">Thursday</small><small
                                            class="fw-bold d-md-none">Thu</small></th>
                                    <th class="text-center"><small class="fw-bold d-none d-md-inline">Friday</small><small
                                            class="fw-bold d-md-none">Fri</small></th>
                                    <th class="text-center"><small
                                            class="fw-bold d-none d-md-inline">Saturday</small><small
                                            class="fw-bold d-md-none">Sat</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 7:00-8:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">7:00–8:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 8:00-9:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">8:00–9:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 9:00-10:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">9:00–10:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 10:00-11:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">10:00–11:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 11:00-12:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">11:00–12:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- Lunch Break -->
                                <tr class="table-warning">
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">12:00–1:00</small>
                                    </td>
                                    <td colspan="6" class="text-center text-muted">
                                        <small><em>Lunch Break</em></small>
                                    </td>
                                </tr>
                                <!-- 1:00-2:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">1:00–2:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 2:00-3:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">2:00–3:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 3:00-4:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">3:00–4:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 4:00-5:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">4:00–5:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                                <!-- 5:00-6:00 -->
                                <tr>
                                    <td class="text-center bg-light time-cell align-middle">
                                        <small class="fw-semibold">5:00–6:00</small>
                                    </td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                    <td class="schedule-cell"><span class="text-muted">—</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile: Card View (Alternative) -->
                    <div id="scheduleMobileView" class="d-block d-md-none mt-3">
                        <p class="text-muted text-center small mb-2">
                            <i class="fa-solid fa-arrows-left-right me-1"></i>Scroll horizontally to view all days
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Styles -->
    <style>
        /* Quick Cards */
        .quick-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .quick-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* Maroon Theme */
        .bg-maroon {
            background-color: #660000 !important;
        }

        .text-maroon {
            color: #660000 !important;
        }

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

        .btn-maroon:focus,
        .btn-maroon:active {
            background-color: #660000;
            border-color: #660000;
            box-shadow: 0 0 0 0.25rem rgba(102, 0, 0, 0.25);
        }

        /* Schedule Table */
        .schedule-table {
            font-size: 0.9rem;
        }

        .schedule-table thead th {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            vertical-align: middle;
            padding: 0.75rem 0.5rem;
        }

        .schedule-cell {
            vertical-align: top;
            padding: 0.75rem 0.5rem;
            min-height: 80px;
            background-color: #ffffff;
        }

        .schedule-cell .class-card {
            background-color: #f8f9fa;
            border-left: 3px solid #660000;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .schedule-cell .subject-code {
            font-weight: 600;
            color: #660000;
            font-size: 0.9rem;
        }

        .schedule-cell .subject-name {
            color: #212529;
            font-size: 0.8rem;
        }

        .schedule-cell .instructor {
            color: #6c757d;
            font-size: 0.75rem;
        }

        .schedule-cell .room {
            color: #6c757d;
            font-size: 0.75rem;
        }

        .time-cell {
            font-weight: 500;
        }

        /* Form Responsive Sizes */
        @media (min-width: 768px) {
            .form-select-md-normal {
                font-size: 1rem;
                padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            }

            .btn-md-normal {
                font-size: 1rem;
                padding: 0.375rem 0.75rem;
            }

            .h5-md {
                font-size: 1.25rem;
            }

            .h4-md {
                font-size: 1.5rem;
            }
        }

        /* Mobile Optimizations */
        @media (max-width: 767px) {

            /* Prevent horizontal overflow */
            body,
            html {
                overflow-x: hidden;
                max-width: 100vw;
            }

            .container-fluid {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
                max-width: 100%;
            }

            /* Card spacing */
            .card {
                border-radius: 0.5rem;
                margin-left: 0;
                margin-right: 0;
            }

            .card-body {
                padding: 0.75rem !important;
            }

            /* Compact form fields */
            .form-label {
                margin-bottom: 0.25rem;
            }

            /* Ensure table container doesn't overflow */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .schedule-table {
                font-size: 0.75rem;
                min-width: 600px;
            }

            .schedule-table thead th {
                padding: 0.5rem 0.25rem;
            }

            .schedule-cell {
                padding: 0.5rem 0.25rem;
                min-height: 60px;
            }

            .schedule-cell .class-card {
                padding: 0.35rem;
                font-size: 0.7rem;
            }

            .schedule-cell .subject-code {
                font-size: 0.75rem;
            }

            .schedule-cell .subject-name {
                font-size: 0.7rem;
            }

            .schedule-cell .instructor,
            .schedule-cell .room {
                font-size: 0.65rem;
            }

            .time-column {
                width: 70px !important;
            }

            .card-body {
                padding: 1rem !important;
            }
        }

        /* Touch-friendly tap targets on mobile */
        @media (max-width: 576px) {
            .btn {
                min-height: 44px;
            }

            .form-select,
            .form-control {
                min-height: 44px;
            }
        }

        /* Table Scroll Shadow Effect */
        .table-responsive {
            position: relative;
        }

        /* Accessibility: High Contrast */
        .schedule-cell:hover {
            background-color: #f8f9fa;
        }
    </style>

    <!-- Frontend Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('scheduleFilterForm');
            const emptyState = document.getElementById('emptyState');
            const scheduleTableView = document.getElementById('scheduleTableView');
            const scheduleInfo = document.getElementById('scheduleInfo');
            const displayScheduleDetails = document.getElementById('displayScheduleDetails');
            const resetFiltersBtn = document.getElementById('resetFiltersBtn');
            const viewScheduleBtn = document.getElementById('viewScheduleBtn');

            // View Schedule
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate
                if (!filterForm.checkValidity()) {
                    filterForm.classList.add('was-validated');
                    return;
                }

                // Get selected values
                const academicYear = document.getElementById('filterAcademicYear').options[document
                    .getElementById('filterAcademicYear').selectedIndex].text;
                const semester = document.getElementById('filterSemester').options[document.getElementById(
                    'filterSemester').selectedIndex].text;
                const program = document.getElementById('filterProgram').value;
                const yearLevel = document.getElementById('filterYearLevel').options[document
                    .getElementById('filterYearLevel').selectedIndex].text;
                const block = document.getElementById('filterBlock').value;

                // Update schedule info
                displayScheduleDetails.textContent =
                    `${program} ${yearLevel} - ${block} | ${academicYear} (${semester})`;

                // Show schedule, hide empty state
                emptyState.classList.add('d-none');
                scheduleTableView.classList.remove('d-none');
                scheduleInfo.classList.remove('d-none');

                // Scroll to schedule
                scheduleTableView.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            });

            // Reset Filters
            resetFiltersBtn.addEventListener('click', function() {
                filterForm.reset();
                filterForm.classList.remove('was-validated');
                emptyState.classList.remove('d-none');
                scheduleTableView.classList.add('d-none');
                scheduleInfo.classList.add('d-none');
            });
        });
    </script>
@endsection
