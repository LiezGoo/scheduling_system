<!-- View Workload Configuration Modal -->
<div class="modal fade" id="viewWorkloadModal" tabindex="-1" aria-labelledby="viewWorkloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="viewWorkloadModalLabel">
                    <i class="fa-solid fa-hourglass-end me-2"></i>Faculty Workload Configuration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body view-workload-body">
                <!-- Faculty Information Section -->
                <div class="card view-info-card mb-3 border-0">
                    <div class="card-body">
                        <h6 class="card-title section-label mb-3">
                            <i class="fa-solid fa-user-tie me-2"></i>Faculty Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small mb-1">Faculty Name</label>
                                <div class="info-value" id="viewFacultyName">-</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small mb-1">Department</label>
                                <div class="info-value" id="viewDepartmentName">-</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small mb-1">Status</label>
                                <div>
                                    <span class="badge rounded-pill px-3 py-2" id="viewStatus">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teaching Load Constraints Section -->
                <div class="card view-info-card mb-3 border-0">
                    <div class="card-body">
                        <h6 class="card-title section-label mb-3">
                            <i class="fa-solid fa-chart-bar me-2"></i>Teaching Load Constraints
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="constraint-card text-center p-3 rounded">
                                    <div class="text-muted small mb-1">Lecture Hours/Week</div>
                                    <div class="h5 mb-0 text-primary fw-bold" id="viewMaxLectureHours">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="constraint-card text-center p-3 rounded">
                                    <div class="text-muted small mb-1">Lab Hours/Week</div>
                                    <div class="h5 mb-0 text-teal fw-bold" id="viewMaxLabHours">-</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="constraint-card text-center p-3 rounded">
                                    <div class="text-muted small mb-1">Max Hours/Day</div>
                                    <div class="h5 mb-0 text-orange fw-bold" id="viewMaxHoursPerDay">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teaching Availability Section -->
                <div class="card view-info-card mb-3 border-0">
                    <div class="card-body">
                        <h6 class="card-title section-label mb-3">
                            <i class="fa-solid fa-calendar-alt me-2"></i>Teaching Availability
                        </h6>
                        <div class="availability-header d-none d-md-grid mb-2">
                            <span>Day</span>
                            <span>Start Time</span>
                            <span>End Time</span>
                            <span>Status</span>
                        </div>
                        <div id="viewAvailabilitySchedule" class="availability-schedule">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    #viewWorkloadModal .modal-header {
        background-color: #660000;
        color: #ffffff;
        padding: 1.25rem;
    }

    #viewWorkloadModal .modal-footer {
        background-color: #ffffff;
        color: #212529;
        padding: 1rem 1.25rem;
    }

    #viewWorkloadModal .modal-title {
        font-size: 1.1rem;
        font-weight: 600;
    }

    #viewWorkloadModal .view-workload-body {
        background: #f6f7fb;
    }

    #viewWorkloadModal .view-info-card {
        background: #ffffff;
        border-radius: 0.8rem;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.06);
    }

    #viewWorkloadModal .section-label {
        color: #660000;
        font-weight: 700;
    }

    #viewWorkloadModal .info-value {
        color: #1f2937;
        font-weight: 600;
        font-size: 0.98rem;
    }

    #viewWorkloadModal .constraint-card {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }

    #viewWorkloadModal .text-teal {
        color: #0f766e;
    }

    #viewWorkloadModal .text-orange {
        color: #c2410c;
    }

    #viewWorkloadModal .availability-header {
        display: grid;
        grid-template-columns: 1.15fr 1fr 1fr auto;
        gap: 0.75rem;
        color: #6b7280;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 0 0.75rem;
    }

    #viewWorkloadModal .availability-schedule {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    #viewWorkloadModal .availability-item {
        display: grid;
        grid-template-columns: 1.15fr 1fr 1fr auto;
        gap: 0.75rem;
        align-items: center;
        padding: 0.8rem 0.9rem;
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.6rem;
    }

    #viewWorkloadModal .availability-item.disabled {
        background-color: #f8f9fa;
    }

    #viewWorkloadModal .availability-day {
        font-weight: 500;
        color: #495057;
    }

    #viewWorkloadModal .availability-time {
        color: #0d6efd;
        font-weight: 600;
    }

    #viewWorkloadModal .availability-time-muted {
        color: #9ca3af;
    }

    #viewWorkloadModal .availability-badge {
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 999px;
        padding: 0.33rem 0.72rem;
    }

    @media (max-width: 767.98px) {
        #viewWorkloadModal .availability-item {
            grid-template-columns: 1fr;
            gap: 0.3rem;
        }
    }
</style>
