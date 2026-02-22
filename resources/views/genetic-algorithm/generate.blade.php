@extends('layouts.app')

@section('title', 'Schedule Generation - Genetic Algorithm')

@section('content')
<div class="container-fluid py-5" style="background-color: #f8f9fa;">
    <!-- Header Section -->
    <div class="mb-4">
        <h1 class="h2 fw-bold" style="color: #660000;">
            <i class="fas fa-dna me-2"></i> Schedule Generation
        </h1>
        <p class="text-muted">Generate optimized schedules using Genetic Algorithm</p>
    </div>

    <!-- Main Layout Container -->
    <div class="row g-4">
        <!-- LEFT COLUMN: Configuration Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header" style="background-color: #660000; color: white; border-radius: 8px 8px 0 0;">
                    <h5 class="mb-0">
                        <i class="fas fa-sliders-h me-2"></i> Schedule Configuration
                    </h5>
                </div>

                <div class="card-body">
                    <form id="scheduleConfigForm">
                        @csrf

                        <!-- Basic Schedule Information -->
                        <div class="mb-3">
                            <label for="academicYear" class="form-label fw-semibold">
                                Academic Year
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <select class="form-select form-select-sm" id="academicYear" name="academic_year_id" required>
                                <option value="">-- Select Academic Year --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ old('academic_year_id', request('academic_year_id')) == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="semester" class="form-label fw-semibold">
                                Semester
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <select class="form-select form-select-sm" id="semester" name="semester" required>
                                <option value="">-- Select Semester --</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                                <option value="3">Summer</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label fw-semibold">
                                Department
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <select class="form-select form-select-sm" id="department" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <option value="1">Computer Science</option>
                                <option value="2">Engineering</option>
                                <option value="3">Business</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="program" class="form-label fw-semibold">
                                Program
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <select class="form-select form-select-sm" id="program" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                <option value="1">BS Computer Science</option>
                                <option value="2">BS Information Technology</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="yearLevel" class="form-label fw-semibold">
                                Year Level
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <select class="form-select form-select-sm" id="yearLevel" name="year_level" required>
                                <option value="">-- Select Year Level --</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="numberOfBlocks" class="form-label fw-semibold">
                                Number of Blocks
                                <span class="badge bg-light text-dark ms-1">Required</span>
                            </label>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   id="numberOfBlocks" 
                                   name="number_of_blocks" 
                                   min="1" 
                                   max="20" 
                                   step="1"
                                   value="1"
                                   required>
                            <small class="text-muted">Enter how many blocks/sections will be generated (1-20). System will auto-generate Block 1, Block 2, etc.</small>
                        </div>

                        <!-- GA Parameters Section -->
                        <hr class="my-4"/>

                        <h6 class="fw-bold mb-3" style="color: #660000;">
                            <i class="fas fa-microscope me-2"></i> Genetic Algorithm Parameters
                        </h6>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="populationSize" class="form-label fw-semibold mb-0">
                                    Population Size
                                    <a href="javascript:void(0)" class="ms-1" data-bs-toggle="tooltip" 
                                       title="Number of solutions in each generation. Larger population = more diversity but slower computation.">
                                        <i class="fas fa-info-circle text-primary"></i>
                                    </a>
                                </label>
                            </div>
                            <input type="number" class="form-control form-control-sm" id="populationSize" 
                                   name="population_size" value="50" min="10" max="500" required>
                            <small class="text-muted">10-500 (recommended: 50-100)</small>
                        </div>

                        <div class="mb-3">
                            <label for="generations" class="form-label fw-semibold">
                                Number of Generations
                                <a href="javascript:void(0)" class="ms-1" data-bs-toggle="tooltip" 
                                   title="Number of evolution cycles. More generations = better solutions but longer runtime.">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </a>
                            </label>
                            <input type="number" class="form-control form-control-sm" id="generations" 
                                   name="generations" value="100" min="10" max="1000" required>
                            <small class="text-muted">10-1000 (recommended: 100-300)</small>
                        </div>

                        <div class="mb-3">
                            <label for="mutationRate" class="form-label fw-semibold">
                                Mutation Rate (%)
                                <a href="javascript:void(0)" class="ms-1" data-bs-toggle="tooltip" 
                                   title="Probability of random changes in solutions. Higher rate increases exploration but may slow convergence.">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </a>
                            </label>
                            <input type="number" class="form-control form-control-sm" id="mutationRate" 
                                   name="mutation_rate" value="15" min="1" max="100" step="1" required>
                            <small class="text-muted">1-100 (recommended: 10-20%)</small>
                        </div>

                        <div class="mb-3">
                            <label for="crossoverRate" class="form-label fw-semibold">
                                Crossover Rate (%)
                                <a href="javascript:void(0)" class="ms-1" data-bs-toggle="tooltip" 
                                   title="Probability of combining parent solutions. Higher rate emphasizes exploration.">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </a>
                            </label>
                            <input type="number" class="form-control form-control-sm" id="crossoverRate" 
                                   name="crossover_rate" value="80" min="1" max="100" step="1" required>
                            <small class="text-muted">1-100 (recommended: 70-90%)</small>
                        </div>

                        <div class="mb-4">
                            <label for="eliteSize" class="form-label fw-semibold">
                                Elite Size
                                <a href="javascript:void(0)" class="ms-1" data-bs-toggle="tooltip" 
                                   title="Number of best solutions preserved to next generation (elitism). Prevents loss of good solutions.">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </a>
                            </label>
                            <input type="number" class="form-control form-control-sm" id="eliteSize" 
                                   name="elite_size" value="5" min="1" max="50" required>
                            <small class="text-muted">1-50 (recommended: 3-10)</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-lg fw-semibold" 
                                    id="generateScheduleBtn" 
                                    style="background-color: #660000; color: white; border: none;"
                                    onclick="showConfirmationModal()">
                                <i class="fas fa-play-circle me-2"></i> Generate Schedule
                            </button>
                            <button type="reset" class="btn btn-outline-secondary" 
                                    id="resetParametersBtn"
                                    onclick="resetToDefaults()">
                                <i class="fas fa-redo me-2"></i> Reset Parameters
                            </button>
                        </div>

                        @if(auth()->user()->hasRole(['admin']))
                            <div class="alert alert-info alert-sm mt-3 mb-0">
                                <small>
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <strong>Admin Mode:</strong> Full access to all parameters
                                </small>
                            </div>
                        @elseif(auth()->user()->hasRole(['program_head']))
                            <div class="alert alert-info alert-sm mt-3 mb-0">
                                <small>
                                    <i class="fas fa-user-tie me-1"></i>
                                    <strong>Program Head Mode:</strong> Department auto-filled
                                </small>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: GA Execution Status and Controls -->
        <div class="col-lg-8">
            <!-- GA Execution Status Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header" style="background-color: #660000; color: white; border-radius: 8px 8px 0 0;">
                    <h5 class="mb-0">
                        <i class="fas fa-activity me-2"></i> Genetic Algorithm Execution
                    </h5>
                </div>

                <div class="card-body">
                    <!-- Status Badge -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span class="me-2 fw-semibold">Status:</span>
                                <span id="statusBadge" class="badge bg-secondary px-3 py-2">
                                    <i class="fas fa-pause-circle me-1"></i> Idle
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted" id="executionTime">--</small>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-semibold mb-0">Progress</label>
                            <span id="progressPercentage" class="badge bg-light text-dark">0%</span>
                        </div>
                        <div class="progress" style="height: 24px; border-radius: 6px;">
                            <div id="progressBar" class="progress-bar" role="progressbar" 
                                 style="width: 0%; background-color: #660000; border-radius: 6px; transition: width 0.3s ease;"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="row g-3">
                        <!-- Current Generation -->
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa; border-left: 4px solid #660000;">
                                <div class="text-muted small fw-semibold">Current Generation</div>
                                <div class="h5 mb-0 fw-bold" id="currentGeneration" style="color: #660000;">
                                    0 / 100
                                </div>
                            </div>
                        </div>

                        <!-- Best Fitness Score -->
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa; border-left: 4px solid #28a745;">
                                <div class="text-muted small fw-semibold">Best Fitness Score</div>
                                <div class="h5 mb-0 fw-bold" id="bestFitnessScore">
                                    --
                                </div>
                            </div>
                        </div>

                        <!-- Conflict Count -->
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa; border-left: 4px solid #dc3545;">
                                <div class="text-muted small fw-semibold">Conflicts Detected</div>
                                <div class="h5 mb-0 fw-bold" id="conflictCount" style="color: #dc3545;">
                                    --
                                </div>
                            </div>
                        </div>

                        <!-- Penalty Score -->
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa; border-left: 4px solid #ffc107;">
                                <div class="text-muted small fw-semibold">Total Penalty Score</div>
                                <div class="h5 mb-0 fw-bold" id="penaltyScore" style="color: #ffc107;">
                                    --
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Running Simulation Indicator -->
                    <div id="runningIndicator" class="alert alert-warning d-none mt-4 mb-0" role="alert">
                        <div class="spinner-border spinner-border-sm me-2 float-start" role="status">
                            <span class="visually-hidden">Running...</span>
                        </div>
                        <div>
                            <strong>Algorithm Running...</strong><br>
                            <small>Please wait while the genetic algorithm optimizes your schedule.</small>
                        </div>
                    </div>

                    <!-- Success Indicator -->
                    <div id="successIndicator" class="alert alert-success d-none mt-4 mb-0" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Generation Complete!</strong> Schedule preview is ready below.
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                                    id="stopButton" disabled>
                                <i class="fas fa-stop-circle me-1"></i> Stop
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" 
                                    data-bs-toggle="modal" data-bs-target="#historyModal">
                                <i class="fas fa-history me-1"></i> History
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" 
                                    onclick="scrollToPreview()">
                                <i class="fas fa-arrow-down me-1"></i> Preview
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH: Generated Schedule Preview -->
    <div class="row mt-4" id="previewSection">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background-color: #660000; color: white; border-radius: 8px 8px 0 0;">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i> Generated Weekly Schedule
                            </h5>
                        </div>
                        <div class="col-md-6 text-md-end">
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
                    <div id="gridViewContainer" class="schedule-grid-container">
                        <div class="table-responsive">
                            <table class="table mb-0 schedule-table">
                                <thead style="background-color: #f8f9fa; border-top: 2px solid #660000;">
                                    <tr>
                                        <th style="width: 80px; text-align: center;" class="fw-bold">Time</th>
                                        <th style="text-align: center;" class="fw-bold">Monday</th>
                                        <th style="text-align: center;" class="fw-bold">Tuesday</th>
                                        <th style="text-align: center;" class="fw-bold">Wednesday</th>
                                        <th style="text-align: center;" class="fw-bold">Thursday</th>
                                        <th style="text-align: center;" class="fw-bold">Friday</th>
                                        <th style="text-align: center;" class="fw-bold">Saturday</th>
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
                                    <tr style="height: 120px;">
                                        <td style="text-align: center; vertical-align: top; font-weight: bold; color: #660000;">
                                            {{ $hour }}
                                        </td>
                                        @for($day = 0; $day < 6; $day++)
                                        <td style="border: 1px solid #dee2e6; vertical-align: top; padding: 8px;">
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
                        <div class="p-3" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                            <div class="row g-3">
                                <div class="col-md-auto">
                                    <div class="d-flex align-items-center">
                                        <div style="width: 20px; height: 20px; background-color: #007bff; border-radius: 3px; margin-right: 8px;"></div>
                                        <span class="small fw-semibold">Lecture</span>
                                    </div>
                                </div>
                                <div class="col-md-auto">
                                    <div class="d-flex align-items-center">
                                        <div style="width: 20px; height: 20px; background-color: #28a745; border-radius: 3px; margin-right: 8px;"></div>
                                        <span class="small fw-semibold">Laboratory</span>
                                    </div>
                                </div>
                                <div class="col-md-auto">
                                    <div class="d-flex align-items-center">
                                        <div style="width: 20px; height: 20px; background-color: #dc3545; border-radius: 3px; margin-right: 8px;"></div>
                                        <span class="small fw-semibold">Conflict</span>
                                    </div>
                                </div>
                                <div class="col-md-auto">
                                    <div class="d-flex align-items-center">
                                        <div style="width: 20px; height: 20px; background-color: #6c757d; border-radius: 3px; margin-right: 8px;"></div>
                                        <span class="small fw-semibold">Reserved/Break</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View: List Format -->
                    <div id="tableViewContainer" class="d-none p-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead style="background-color: #f8f9fa; border-top: 2px solid #660000;">
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
                                    <!-- Sample rows will be populated -->
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
                <div class="card-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <div class="row g-2">
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    id="exportPdfBtn" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                            </button>
                        </div>
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    id="exportCsvBtn" onclick="exportToCSV()">
                                <i class="fas fa-file-csv me-1"></i> Export CSV
                            </button>
                        </div>
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    id="printBtn" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                        @if(auth()->user()->hasRole(['program_head']))
                        <div class="col-md-auto ms-auto">
                            <button type="button" class="btn btn-sm" 
                                    id="approveSubmitBtn"
                                    style="background-color: #660000; color: white;"
                                    onclick="approveAndSubmit()">
                                <i class="fas fa-check me-1"></i> Approve & Submit
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH: Conflict Summary Panel -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #660000;">
                    <button class="btn btn-link text-decoration-none text-dark w-100 text-start p-0 fw-bold" 
                            data-bs-toggle="collapse" data-bs-target="#conflictSummary" aria-expanded="false">
                        <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>
                        Conflict Summary
                        <i class="fas fa-chevron-down float-end mt-1"></i>
                    </button>
                </div>

                <div id="conflictSummary" class="collapse show">
                    <div class="card-body">
                        <!-- No Conflicts State -->
                        <div id="noConflictsState" class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                            </div>
                            <h5 style="color: #28a745;" class="fw-bold">Conflict-Free Schedule Generated</h5>
                            <p class="text-muted">No scheduling conflicts detected. The schedule is ready for approval.</p>
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-shield-alt me-1"></i> Verified
                            </span>
                        </div>

                        <!-- Conflicts State (Hidden by default) -->
                        <div id="conflictsState" class="d-none">
                            <div class="alert alert-danger mb-3" role="alert">
                                <i class="fas fa-alert-circle me-2"></i>
                                <strong>Conflicts Detected!</strong> Review and resolve before approval.
                            </div>

                            <div class="row g-3">
                                <!-- Room Conflicts -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded border" style="border-left: 4px solid #dc3545 !important;">
                                        <h6 class="fw-bold mb-3" style="color: #dc3545;">
                                            <i class="fas fa-door-open me-2"></i> Room Conflicts
                                        </h6>
                                        <div id="roomConflictsList">
                                            <small class="text-muted">-- No room conflicts --</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instructor Conflicts -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded border" style="border-left: 4px solid #dc3545 !important;">
                                        <h6 class="fw-bold mb-3" style="color: #dc3545;">
                                            <i class="fas fa-user-tie me-2"></i> Instructor Conflicts
                                        </h6>
                                        <div id="instructorConflictsList">
                                            <small class="text-muted">-- No instructor conflicts --</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overlapping Schedules -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded border" style="border-left: 4px solid #ffc107 !important;">
                                        <h6 class="fw-bold mb-3" style="color: #ffc107;">
                                            <i class="fas fa-clock me-2"></i> Overlapping Schedules
                                        </h6>
                                        <div id="overlappingSchedulesList">
                                            <small class="text-muted">-- No overlapping schedules --</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penalty Score -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded border" style="border-left: 4px solid #6c757d !important;">
                                        <h6 class="fw-bold mb-3" style="color: #6c757d;">
                                            <i class="fas fa-chart-bar me-2"></i> Penalty Score Breakdown
                                        </h6>
                                        <div id="penaltyBreakdown">
                                            <small class="text-muted">-- No penalties --</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: #660000; color: white; border: none;">
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
                            <li id="summaryProgram">Program: --</li>
                            <li id="summaryYearLevel">Year Level: --</li>
                            <li id="summaryBlockSection">Block/Section: --</li>
                            <li id="summarySemester">Semester: --</li>
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
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-bold" style="background-color: #660000; color: white;" 
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
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: #660000; color: white; border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-history me-2"></i> Generation History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Date</th>
                                <th>Program</th>
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
<div id="successToast" class="position-fixed bottom-0 end-0 m-3" style="z-index: 9999; display: none;">
    <div class="toast show border-0 shadow" role="alert" style="background-color: #28a745; color: white;">
        <div class="toast-body">
            <i class="fas fa-check-circle me-2"></i>
            <strong id="toastMessage">Schedule generated successfully!</strong>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    /* Maroon Theme Colors */
    :root {
        --maroon: #660000;
        --maroon-light: #8b0000;
        --maroon-lighter: #a52a2a;
    }

    /* Cards and Shadows */
    .card {
        border-radius: 8px;
        border: none;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.3s ease, transform 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }

    /* Form Controls */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.2rem rgba(102, 0, 0, 0.15);
    }

    /* Buttons */
    .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    /* Schedule Grid */
    .schedule-table {
        border-collapse: collapse;
    }

    .schedule-table td, .schedule-table th {
        border: 1px solid #dee2e6;
    }

    .schedule-slot {
        min-height: 100px;
        padding: 6px;
        font-size: 0.75rem;
        overflow: hidden;
    }

    .schedule-item {
        background-color: #007bff;
        color: white;
        padding: 4px;
        margin-bottom: 4px;
        border-radius: 4px;
        border-left: 4px solid #0056b3;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .schedule-item:hover {
        transform: scale(0.95);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
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

    /* Progress Bar Animation */
    @keyframes slideRight {
        0% {
            width: 0;
        }
    }

    .progress-bar {
        animation: none;
    }

    .progress-bar.animating {
        animation: slideRight 0.5s ease-out;
    }

    /* Loading Spinner */
    .spinner-border-sm {
        width: 1.5rem;
        height: 1.5rem;
        border-width: 0.2em;
    }

    /* Badges */
    .badge {
        border-radius: 6px;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
    }

    /* Alerts */
    .alert {
        border-radius: 6px;
        border: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .schedule-table {
            font-size: 0.75rem;
        }

        .schedule-slot {
            min-height: 80px;
        }

        .card-header h5 {
            font-size: 1.1rem;
        }
    }

    /* Tooltip styling */
    .tooltip-inner {
        background-color: var(--maroon);
        border-radius: 6px;
        padding: 8px 12px;
    }

    /* View Toggle Button Group */
    .btn-group-sm .btn {
        padding: 0.4rem 0.8rem;
    }

    /* Collapse Animation */
    .collapse {
        transition: all 0.3s ease;
    }

    /* Print Styles */
    @media print {
        .btn, .alert-warning, .alert-info {
            display: none !important;
        }

        .card {
            box-shadow: none;
            border: 1px solid #dee2e6;
        }

        .schedule-table {
            font-size: 10pt;
        }
    }
</style>
@endsection

@section('scripts')
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

    // Show Confirmation Modal
    function showConfirmationModal() {
        // Populate summary
        document.getElementById('summaryProgram').textContent = 
            'Program: ' + (document.getElementById('program').options[document.getElementById('program').selectedIndex].text || '--');
        document.getElementById('summaryYearLevel').textContent = 
            'Year Level: ' + (document.getElementById('yearLevel').options[document.getElementById('yearLevel').selectedIndex].text || '--');
        document.getElementById('summaryBlockSection').textContent = 
            'Number of Blocks: ' + (document.getElementById('numberOfBlocks').value || '--');
        document.getElementById('summarySemester').textContent = 
            'Semester: ' + (document.getElementById('semester').options[document.getElementById('semester').selectedIndex].text || '--');

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
        totalGenerations = parseInt(document.getElementById('generations').value);
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        modal.hide();

        // Disable form inputs
        disableFormInputs(true);

        // Update UI
        updateStatus('Running');
        document.getElementById('runningIndicator').classList.remove('d-none');
        document.getElementById('successIndicator').classList.add('d-none');
        document.getElementById('stopButton').disabled = false;
        document.getElementById('generateScheduleBtn').disabled = true;

        // Simulate generation progress
        simulateGeneration();
    }

    // Simulate Generation Progress
    function simulateGeneration() {
        let progress = 0;
        const interval = Math.random() * 500 + 300; // Random interval between 300-800ms

        const progressInterval = setInterval(() => {
            if (!isRunning) {
                clearInterval(progressInterval);
                return;
            }

            progress += Math.random() * 15; // Random progress increment
            if (progress > 100) progress = 100;

            currentGeneration = Math.floor((progress / 100) * totalGenerations);

            // Update UI
            updateProgress(progress);
            updateCurrentGeneration(currentGeneration, totalGenerations);
            updateFitnessScore(Math.random() * 100);
            updateConflictCount(Math.floor(Math.random() * 5));

            if (progress >= 100) {
                clearInterval(progressInterval);
                completeGeneration();
            }
        }, interval);

        // Stop button handler
        document.getElementById('stopButton').onclick = () => {
            isRunning = false;
            clearInterval(progressInterval);
            cancelGeneration();
        };
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
        updateStatus('Completed');
        document.getElementById('runningIndicator').classList.add('d-none');
        document.getElementById('successIndicator').classList.remove('d-none');
        disableFormInputs(false);
        document.getElementById('stopButton').disabled = true;
        document.getElementById('generateScheduleBtn').disabled = false;

        // Populate sample schedule
        populateSampleSchedule();

        // Show success toast
        showToast('Schedule generated successfully!');

        // Show success badge in conflict summary
        showConflictSummary(false);
    }

    // Cancel Generation
    function cancelGeneration() {
        updateStatus('Idle');
        document.getElementById('runningIndicator').classList.add('d-none');
        disableFormInputs(false);
        document.getElementById('stopButton').disabled = true;
        document.getElementById('generateScheduleBtn').disabled = false;
        updateProgress(0);
        updateCurrentGeneration(0, 0);
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

    // Populate Sample Schedule
    function populateSampleSchedule() {
        // Sample data
        const sampleSchedule = [
            {subject: 'CS-101', instructor: 'Dr. Smith', room: 'Lab 301', day: 'Monday', time: '09:00', type: 'lecture'},
            {subject: 'CS-102', instructor: 'Prof. Jones', room: 'Lab 302', day: 'Tuesday', time: '10:00', type: 'lab'},
            {subject: 'CS-103', instructor: 'Dr. Brown', room: 'Room 405', day: 'Wednesday', time: '13:00', type: 'lecture'},
            {subject: 'CS-104', instructor: 'Prof. Lee', room: 'Lab 301', day: 'Thursday', time: '14:00', type: 'lab'},
            {subject: 'CS-105', instructor: 'Dr. Davis', room: 'Room 406', day: 'Friday', time: '11:00', type: 'lecture'},
        ];

        // Populate grid view
        sampleSchedule.forEach(item => {
            const slot = document.querySelector(`[data-day="${getDay(item.day)}"][data-time="${item.time}"]`);
            if (slot) {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = `schedule-item ${item.type}`;
                scheduleItem.innerHTML = `
                    <strong>${item.subject}</strong><br>
                    <small>${item.instructor}</small><br>
                    <small>${item.room}</small>
                `;
                scheduleItem.title = `${item.subject}\n${item.instructor}\n${item.room}\n${item.day} ${item.time}`;
                slot.appendChild(scheduleItem);
            }
        });

        // Populate table view
        const tableBody = document.getElementById('tableViewBody');
        tableBody.innerHTML = sampleSchedule.map(item => `
            <tr>
                <td><strong>${item.subject}</strong></td>
                <td>${item.instructor}</td>
                <td>${item.room}</td>
                <td>${item.day}</td>
                <td>${item.time}</td>
                <td><span class="badge bg-info">${item.type.charAt(0).toUpperCase() + item.type.slice(1)}</span></td>
                <td><span class="badge bg-success">Scheduled</span></td>
            </tr>
        `).join('');
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
        showToast('Exporting to PDF... (Demo)');
        // TODO: Implement PDF export using a library like jsPDF
    }

    // Export to CSV
    function exportToCSV() {
        showToast('Exporting to CSV... (Demo)');
        // TODO: Implement CSV export
    }

    // Approve and Submit
    function approveAndSubmit() {
        if (confirm('Are you sure you want to approve and submit this schedule?')) {
            showToast('Schedule approved and submitted successfully!');
        }
    }

    // Show Conflict Summary
    function showConflictSummary(hasConflicts) {
        if (hasConflicts) {
            document.getElementById('noConflictsState').classList.add('d-none');
            document.getElementById('conflictsState').classList.remove('d-none');
        } else {
            document.getElementById('noConflictsState').classList.remove('d-none');
            document.getElementById('conflictsState').classList.add('d-none');
        }
    }

    // Show Toast
    function showToast(message) {
        document.getElementById('toastMessage').textContent = message;
        const toast = document.getElementById('successToast');
        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    // Scroll to Preview
    function scrollToPreview() {
        document.getElementById('previewSection').scrollIntoView({behavior: 'smooth'});
    }
</script>
@endsection
