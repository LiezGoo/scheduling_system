@extends('layouts.app')

@section('page-title', 'Faculty Workload Configuration')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">
                <i class="fa-solid fa-user-clock me-2"></i>Define teaching workload limits and availability schemes for each faculty member.
            </p>
        </div>
        <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#configureFacultyModal">
            <i class="fa-solid fa-plus me-2"></i>Configure Faculty Workload
        </button>
    </div>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="#" id="filterForm" novalidate>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="filterSearch" class="form-label">Search Faculty</label>
                        <input type="text" class="form-control" id="filterSearch" name="search"
                            placeholder="Search by faculty name..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="filterDepartment" class="form-label">Department</label>
                        <select class="form-select" id="filterDepartment" name="department">
                            <option value="">All Departments</option>
                            <option value="1">Computer Science</option>
                            <option value="2">Information Technology</option>
                            <option value="3">Engineering</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterContractType" class="form-label">Contract Type</label>
                        <select class="form-select" id="filterContractType" name="contract_type">
                            <option value="">All Types</option>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters"
                            title="Clear Filters">
                            <i class="fa-solid fa-rotate-left me-1"></i>Clear
                        </button>
                        <div class="spinner-border spinner-border-sm text-maroon d-none" role="status"
                            aria-hidden="true" id="filtersSpinner"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="facultyWorkloadTable">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th class="text-center">Contract Type</th>
                            <th class="text-center">Lecture Limit</th>
                            <th class="text-center">Lab Limit</th>
                            <th class="text-center">Max Hours/Day</th>
                            <th>Available Days</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="facultyWorkloadTableBody">
                        <!-- Sample data row -->
                        <tr>
                            <td>
                                <div class="fw-semibold">Dr. Juan Dela Cruz</div>
                                <small class="text-muted">juan.delacruz@sorsu.edu.ph</small>
                            </td>
                            <td>Computer Science</td>
                            <td class="text-center">
                                <span class="badge bg-primary">Full-Time</span>
                            </td>
                            <td class="text-center">21</td>
                            <td class="text-center">12</td>
                            <td class="text-center">6</td>
                            <td>
                                <span class="badge bg-light text-dark border me-1">Mon</span>
                                <span class="badge bg-light text-dark border me-1">Tue</span>
                                <span class="badge bg-light text-dark border me-1">Wed</span>
                                <span class="badge bg-light text-dark border me-1">Thu</span>
                                <span class="badge bg-light text-dark border">Fri</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">Active</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="viewWorkload(1)"
                                            title="View" aria-label="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="editWorkload(1)"
                                            title="Edit" aria-label="Edit Configuration">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteWorkload(1)"
                                            title="Delete" aria-label="Delete Configuration">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty state (shown when no data) -->
                        <tr id="emptyState" style="display: none;">
                            <td colspan="9" class="text-center py-5">
                                <i class="fa-solid fa-user-clock text-muted fa-3x mb-3"></i>
                                <p class="text-muted mb-3">No Faculty Workload Configurations Found</p>
                                <button type="button" class="btn btn-maroon" data-bs-toggle="modal" 
                                        data-bs-target="#configureFacultyModal">
                                    <i class="fa-solid fa-plus me-2"></i>Configure Faculty Workload
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="text-muted small">
                    Showing 1 of 1 configurations
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configure Faculty Workload Modal -->
<div class="modal fade" id="configureFacultyModal" tabindex="-1" aria-labelledby="configureFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="configureFacultyModalLabel">
                    <i class="fa-solid fa-user-clock me-2"></i>Configure Faculty Workload
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="configureFacultyForm" novalidate>
                @csrf
                <div class="modal-body px-4 py-4">
                    <!-- Faculty Selection -->
                    <div class="mb-3">
                        <label for="faculty_id" class="form-label fw-semibold">
                            Faculty <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="faculty_id" name="faculty_id" required>
                            <option value="">Select Faculty Member</option>
                            <option value="1">Dr. Juan Dela Cruz</option>
                            <option value="2">Prof. Maria Santos</option>
                            <option value="3">Dr. Pedro Reyes</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a faculty member.
                        </div>
                    </div>

                    <!-- Department (Auto-filled) -->
                    <div class="mb-3">
                        <label for="department_name" class="form-label fw-semibold">
                            Department
                        </label>
                        <input type="text" class="form-control" id="department_name" 
                               value="Computer Science" readonly disabled>
                        <small class="text-muted">Auto-filled based on faculty assignment</small>
                    </div>

                    <!-- Contract Type -->
                    <div class="mb-3">
                        <label for="contract_type" class="form-label fw-semibold">
                            Contract Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="contract_type" name="contract_type" required>
                            <option value="">Select Contract Type</option>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a contract type.
                        </div>
                    </div>

                    <!-- Workload Limits -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="lecture_limit" class="form-label fw-semibold">
                                Lecture Limit <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="lecture_limit" name="lecture_limit" 
                                   min="1" max="40" placeholder="e.g., 21" required>
                            <small class="text-muted">Units per week</small>
                            <div class="invalid-feedback">
                                Must be greater than 0
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="lab_limit" class="form-label fw-semibold">
                                Lab Limit <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="lab_limit" name="lab_limit" 
                                   min="0" max="40" placeholder="e.g., 12" required>
                            <small class="text-muted">Units per week</small>
                            <div class="invalid-feedback">
                                Must be 0 or greater
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_hours_per_day" class="form-label fw-semibold">
                                Max Hours Per Day <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="max_hours_per_day" name="max_hours_per_day" 
                                   min="1" max="12" placeholder="e.g., 6" required>
                            <small class="text-muted">Hours</small>
                            <div class="invalid-feedback">
                                Must be greater than 0
                            </div>
                        </div>
                    </div>

                    <!-- Teaching Availability -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-3">
                            Teaching Availability <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_monday" 
                                           name="available_days[]" value="Monday" checked>
                                    <label class="form-check-label ms-2 fw-semibold" for="day_monday">
                                        Monday
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_tuesday" 
                                           name="available_days[]" value="Tuesday" checked>
                                    <label class="form-check-label ms-2 fw-semibold" for="day_tuesday">
                                        Tuesday
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_wednesday" 
                                           name="available_days[]" value="Wednesday" checked>
                                    <label class="form-check-label ms-2 fw-semibold" for="day_wednesday">
                                        Wednesday
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_thursday" 
                                           name="available_days[]" value="Thursday" checked>
                                    <label class="form-check-label ms-2 fw-semibold" for="day_thursday">
                                        Thursday
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_friday" 
                                           name="available_days[]" value="Friday" checked>
                                    <label class="form-check-label ms-2 fw-semibold" for="day_friday">
                                        Friday
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="day_saturday" 
                                           name="available_days[]" value="Saturday">
                                    <label class="form-check-label ms-2 fw-semibold" for="day_saturday">
                                        Saturday
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Select at least one day</small>
                    </div>

                    <!-- Optional Time Range -->
                    <div class="card bg-light border-0 p-3 mb-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-clock me-2"></i>Preferred Teaching Time Range (Optional)
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" value="08:00">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" value="17:00">
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Leave blank to allow teaching at any time during the day
                        </small>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-info border-0 rounded-2 mb-0" role="alert">
                        <small class="text-info">
                            <i class="fa-solid fa-lightbulb me-1"></i>
                            These settings will be used for faculty load assignment, schedule generation, and conflict detection.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold" id="saveConfigBtn">
                        <i class="fa-solid fa-save me-2"></i>Save Configuration
                        <span id="saveConfigSpinner" class="spinner-border spinner-border-sm ms-2" 
                              role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Faculty Workload Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="editFacultyModalLabel">
                    <i class="fa-solid fa-pencil me-2"></i>Edit Faculty Workload
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFacultyForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_workload_id" name="workload_id">
                <div class="modal-body px-4 py-4">
                    <!-- Same form fields as configure modal -->
                    <div class="mb-3">
                        <label for="edit_faculty_name" class="form-label fw-semibold">Faculty</label>
                        <input type="text" class="form-control" id="edit_faculty_name" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label for="edit_department_name" class="form-label fw-semibold">Department</label>
                        <input type="text" class="form-control" id="edit_department_name" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label for="edit_contract_type" class="form-label fw-semibold">
                            Contract Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_contract_type" name="contract_type" required>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_lecture_limit" class="form-label fw-semibold">
                                Lecture Limit <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_lecture_limit" name="lecture_limit" min="1" required>
                            <small class="text-muted">Units per week</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_lab_limit" class="form-label fw-semibold">
                                Lab Limit <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_lab_limit" name="lab_limit" min="0" required>
                            <small class="text-muted">Units per week</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_max_hours_per_day" class="form-label fw-semibold">
                                Max Hours Per Day <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_max_hours_per_day" name="max_hours_per_day" min="1" required>
                            <small class="text-muted">Hours</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-3">Teaching Availability</label>
                        <div class="row g-2">
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_monday" 
                                           name="available_days[]" value="Monday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_monday">Monday</label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_tuesday" 
                                           name="available_days[]" value="Tuesday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_tuesday">Tuesday</label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_wednesday" 
                                           name="available_days[]" value="Wednesday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_wednesday">Wednesday</label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_thursday" 
                                           name="available_days[]" value="Thursday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_thursday">Thursday</label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_friday" 
                                           name="available_days[]" value="Friday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_friday">Friday</label>
                                </div>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="form-check form-check-inline w-100 border rounded p-3">
                                    <input class="form-check-input" type="checkbox" id="edit_day_saturday" 
                                           name="available_days[]" value="Saturday">
                                    <label class="form-check-label ms-2 fw-semibold" for="edit_day_saturday">Saturday</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mb-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-clock me-2"></i>Preferred Teaching Time Range (Optional)
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="edit_start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="edit_end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon fw-semibold" id="updateConfigBtn">
                        <i class="fa-solid fa-save me-2"></i>Update Configuration
                        <span id="updateConfigSpinner" class="spinner-border spinner-border-sm ms-2" 
                              role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Faculty Workload Modal -->
<div class="modal fade" id="viewFacultyModal" tabindex="-1" aria-labelledby="viewFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-semibold" id="viewFacultyModalLabel">
                    <i class="fa-solid fa-eye me-2"></i>Faculty Workload Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Faculty Name</label>
                        <p class="fw-semibold" id="view_faculty_name">Dr. Juan Dela Cruz</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Department</label>
                        <p class="fw-semibold" id="view_department_name">Computer Science</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Contract Type</label>
                        <p><span class="badge bg-primary" id="view_contract_type">Full-Time</span></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <p><span class="badge bg-success" id="view_status">Active</span></p>
                    </div>
                </div>
                <hr>
                <h6 class="fw-semibold mb-3">Workload Limits</h6>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Lecture Limit</label>
                        <p class="fw-semibold fs-4 text-maroon" id="view_lecture_limit">21 <small class="text-muted fs-6">units/week</small></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Lab Limit</label>
                        <p class="fw-semibold fs-4 text-maroon" id="view_lab_limit">12 <small class="text-muted fs-6">units/week</small></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Max Hours Per Day</label>
                        <p class="fw-semibold fs-4 text-maroon" id="view_max_hours">6 <small class="text-muted fs-6">hours</small></p>
                    </div>
                </div>
                <hr>
                <h6 class="fw-semibold mb-3">Teaching Availability</h6>
                <div class="mb-3">
                    <label class="text-muted small d-block mb-2">Available Days</label>
                    <div id="view_available_days">
                        <span class="badge bg-light text-dark border me-1">Mon</span>
                        <span class="badge bg-light text-dark border me-1">Tue</span>
                        <span class="badge bg-light text-dark border me-1">Wed</span>
                        <span class="badge bg-light text-dark border me-1">Thu</span>
                        <span class="badge bg-light text-dark border">Fri</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-muted small">Preferred Start Time</label>
                        <p class="fw-semibold" id="view_start_time">8:00 AM</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Preferred End Time</label>
                        <p class="fw-semibold" id="view_end_time">5:00 PM</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Faculty Workload Modal -->
<div class="modal fade" id="deleteFacultyModal" tabindex="-1" aria-labelledby="deleteFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-semibold" id="deleteFacultyModalLabel">
                    <i class="fa-solid fa-trash me-2"></i>Delete Faculty Workload Configuration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="text-center mb-3">
                    <i class="fa-solid fa-exclamation-triangle text-warning fa-3x"></i>
                </div>
                <p class="text-center mb-3">Are you sure you want to delete the workload configuration for <strong id="delete_faculty_name">Dr. Juan Dela Cruz</strong>?</p>
                <p class="text-muted text-center mb-0">
                    <small><i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.</small>
                </p>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-semibold" id="confirmDeleteBtn" onclick="confirmDeleteWorkload()">
                    <i class="fa-solid fa-trash me-2"></i>Delete Configuration
                    <span id="deleteConfigSpinner" class="spinner-border spinner-border-sm ms-2" 
                          role="status" aria-hidden="true" style="display: none;"></span>
                </button>
            </div>
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

    .bg-maroon {
        background-color: #660000 !important;
    }

    .form-check-input:checked {
        background-color: #660000;
        border-color: #660000;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 0, 0, 0.05);
    }

    .modal-dialog-scrollable .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
</style>
@endpush

@push('scripts')
<script>
    // Clear Filters
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterDepartment').selectedIndex = 0;
        document.getElementById('filterContractType').selectedIndex = 0;
    });

    // Configure Faculty Form Submit
    document.getElementById('configureFacultyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        // Check if at least one day is selected
        const checkedDays = document.querySelectorAll('input[name="available_days[]"]:checked');
        if (checkedDays.length === 0) {
            window.showToast('Please select at least one available day', 'warning');
            return;
        }

        const saveBtn = document.getElementById('saveConfigBtn');
        const spinner = document.getElementById('saveConfigSpinner');
        
        saveBtn.disabled = true;
        spinner.style.display = 'inline-block';

        // Simulate API call
        setTimeout(() => {
            window.showToast('Faculty workload configuration saved successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('configureFacultyModal'));
            modal.hide();
            this.reset();
            this.classList.remove('was-validated');
            
            saveBtn.disabled = false;
            spinner.style.display = 'none';
            
            // Reload page to show new data
            setTimeout(() => location.reload(), 500);
        }, 1500);
    });

    // Edit Faculty Form Submit
    document.getElementById('editFacultyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        const updateBtn = document.getElementById('updateConfigBtn');
        const spinner = document.getElementById('updateConfigSpinner');
        
        updateBtn.disabled = true;
        spinner.style.display = 'inline-block';

        // Simulate API call
        setTimeout(() => {
            window.showToast('Faculty workload configuration updated', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editFacultyModal'));
            modal.hide();
            
            updateBtn.disabled = false;
            spinner.style.display = 'none';
            
            setTimeout(() => location.reload(), 500);
        }, 1500);
    });

    // View Workload
    function viewWorkload(id) {
        // Populate modal with data
        const modal = new bootstrap.Modal(document.getElementById('viewFacultyModal'));
        modal.show();
    }

    // Edit Workload
    function editWorkload(id) {
        // Populate form with data
        document.getElementById('edit_workload_id').value = id;
        document.getElementById('edit_faculty_name').value = 'Dr. Juan Dela Cruz';
        document.getElementById('edit_department_name').value = 'Computer Science';
        document.getElementById('edit_contract_type').value = 'Full-Time';
        document.getElementById('edit_lecture_limit').value = 21;
        document.getElementById('edit_lab_limit').value = 12;
        document.getElementById('edit_max_hours_per_day').value = 6;
        document.getElementById('edit_start_time').value = '08:00';
        document.getElementById('edit_end_time').value = '17:00';
        
        // Check available days
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
            document.getElementById('edit_day_' + day).checked = true;
        });
        
        const modal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
        modal.show();
    }

    // Delete Workload
    let deleteWorkloadId = null;
    function deleteWorkload(id) {
        deleteWorkloadId = id;
        document.getElementById('delete_faculty_name').textContent = 'Dr. Juan Dela Cruz';
        const modal = new bootstrap.Modal(document.getElementById('deleteFacultyModal'));
        modal.show();
    }

    // Confirm Delete
    function confirmDeleteWorkload() {
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const spinner = document.getElementById('deleteConfigSpinner');
        
        deleteBtn.disabled = true;
        spinner.style.display = 'inline-block';

        // Simulate API call
        setTimeout(() => {
            window.showToast('Faculty workload configuration deleted', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteFacultyModal'));
            modal.hide();
            
            deleteBtn.disabled = false;
            spinner.style.display = 'none';
            
            setTimeout(() => location.reload(), 500);
        }, 1500);
    }

    // Auto-fill department when faculty is selected
    document.getElementById('faculty_id').addEventListener('change', function() {
        const departmentField = document.getElementById('department_name');
        if (this.value) {
            // In real implementation, fetch department from API
            departmentField.value = 'Computer Science';
        } else {
            departmentField.value = '';
        }
    });

    // Validation for numeric fields
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function() {
            const min = parseInt(this.getAttribute('min'));
            const max = parseInt(this.getAttribute('max'));
            const value = parseInt(this.value);
            
            if (value < min) this.value = min;
            if (max && value > max) this.value = max;
        });
    });
</script>
@endpush
