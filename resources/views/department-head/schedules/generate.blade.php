@extends('layouts.app')

@section('page-title', 'Generate Schedule')

@push('styles')
<style>
    :root {
        --maroon: #800000;
        --maroon-dark: #600000;
        --maroon-light: rgba(128, 0, 0, 0.05);
        --grid-border: #e9ecef;
        --lecture-blue: #0d6efd;
        --lecture-blue-dark: #0a58ca;
        --lab-green: #28a745;
        --lab-green-dark: #1e7e34;
    }

    .card-maroon {
        border-top: 4px solid var(--maroon);
    }

    .bg-maroon {
        background-color: var(--maroon) !important;
    }

    .btn-maroon {
        background-color: var(--maroon);
        border-color: var(--maroon);
        color: white;
        transition: all 0.2s ease;
    }

    .btn-maroon:hover {
        background-color: var(--maroon-dark);
        border-color: var(--maroon-dark);
        color: white;
        transform: translateY(-1px);
    }

    .schedule-grid-container {
        background: white;
        border-radius: 8px;
        overflow: visible; /* FIXED: was 'hidden', clipped multi-hour items */
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Wrapper that holds table + overlay */
    .grid-wrapper {
        position: relative;
    }

    .schedule-table {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .schedule-table th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 12px 8px;
        text-align: center;
        border: 1px solid var(--grid-border);
    }

    .time-column {
        width: 80px;
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.7rem;
        color: #6c757d;
        text-align: center;
        vertical-align: middle;
        border-right: 2px solid var(--grid-border);
    }

    .schedule-slot {
        height: 80px;
        border: 1px solid var(--grid-border);
        padding: 0;
        vertical-align: top;
        transition: background-color 0.2s;
        position: relative;
        overflow: hidden;
    }

    .schedule-slot:hover {
        background-color: var(--maroon-light);
    }

    /* Overlay — no bottom: so items are never clipped vertically */
    #scheduleOverlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        pointer-events: none;
        z-index: 5;
    }

    .schedule-item {
        background: linear-gradient(135deg, var(--lecture-blue), var(--lecture-blue-dark));
        color: white;
        padding: 6px 8px;
        border-radius: 4px;
        font-size: 0.65rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        border-left: 4px solid rgba(255,255,255,0.35);
        cursor: help;
        animation: fadeIn 0.3s ease-out;
        position: absolute;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        pointer-events: all;
        overflow: hidden;
        box-sizing: border-box;
    }

    .schedule-item.lab {
        background: linear-gradient(135deg, var(--lab-green), var(--lab-green-dark));
    }

    .lecture-badge {
        background: rgba(13, 110, 253, 0.12);
        color: var(--lecture-blue-dark);
        border: 1px solid rgba(13, 110, 253, 0.25);
    }

    .lab-badge {
        background: rgba(40, 167, 69, 0.12);
        color: var(--lab-green-dark);
        border: 1px solid rgba(40, 167, 69, 0.25);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to   { opacity: 1; transform: scale(1); }
    }

    .form-label-custom {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .focus-maroon:focus {
        border-color: var(--maroon);
        box-shadow: 0 0 0 0.2rem var(--maroon-light);
    }

    .legend-indicator {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        display: inline-block;
        margin-right: 6px;
    }

    #tableViewContainer {
        overflow-x: auto;
    }

    #scheduleReviewTable {
        width: 100%;
        min-width: 860px;
        table-layout: fixed;
        margin-bottom: 0;
    }

    #scheduleReviewTable th,
    #scheduleReviewTable td {
        vertical-align: middle;
    }

    #scheduleReviewTable th:nth-child(1),
    #scheduleReviewTable td:nth-child(1) { width: 52px; text-align: center; }
    #scheduleReviewTable th:nth-child(2),
    #scheduleReviewTable td:nth-child(2) { width: 240px; }
    #scheduleReviewTable th:nth-child(3),
    #scheduleReviewTable td:nth-child(3) { width: 90px; }
    #scheduleReviewTable th:nth-child(4),
    #scheduleReviewTable td:nth-child(4) { width: 150px; white-space: nowrap; }
    #scheduleReviewTable th:nth-child(5),
    #scheduleReviewTable td:nth-child(5) { width: 180px; }
    #scheduleReviewTable th:nth-child(6),
    #scheduleReviewTable td:nth-child(6) { width: 120px; }
    #scheduleReviewTable th:nth-child(7),
    #scheduleReviewTable td:nth-child(7) { width: 90px; text-align: center; }

    .schedule-table-subject,
    .schedule-table-instructor {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ========== PRINT STYLES ========== */
    @media print {
        body > *:not(#print-area),
        nav, header, footer, aside,
        .container-fluid > .row > .col-lg-3,
        #welcomePlaceholder,
        .card-header .btn-group,
        #blockSelectorContainer,
        .card-footer {
            display: none !important;
        }

        body { background: white !important; margin: 0; }

        #previewSection {
            display: block !important;
            box-shadow: none !important;
            border: none !important;
        }

        .schedule-grid-container { box-shadow: none !important; }

        .schedule-item {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            animation: none !important;
        }

        .schedule-slot:hover { background-color: transparent !important; }

        #tableViewContainer { overflow: visible !important; }

        .table { font-size: 0.75rem; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Configuration Side -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm card-maroon">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="fas fa-sliders-h me-2 text-maroon"></i> Parameters
                    </h5>
                    <hr class="mb-4">

                    <form id="scheduleConfigForm">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label-custom">Program</label>
                            <select class="form-select focus-maroon" id="programSelect" name="program_id" required>
                                <option value="">Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" {{ (string) old('program_id', $defaultProgramId ?? '') === (string) $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Academic Year</label>
                            <select class="form-select focus-maroon" id="academicYear" name="academic_year_id" required>
                                <option value="">Select Academic Year</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (string) old('academic_year_id', $defaultAcademicYearId ?? '') === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Semester</label>
                            <select class="form-select focus-maroon" id="semester" name="semester_id" required>
                                <option value="">Select Semester</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ (string) old('semester_id', $defaultSemesterId ?? '') === (string) $semester->id ? 'selected' : '' }}>{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Year Level</label>
                            <select class="form-select focus-maroon" id="yearLevel" name="year_level_id" required>
                                <option value="">Select Year Level</option>
                                @foreach ($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel->id }}" {{ (string) old('year_level_id', $defaultYearLevelId ?? '') === (string) $yearLevel->id ? 'selected' : '' }}>
                                        {{ $yearLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">Number of Blocks</label>
                            <input type="number" class="form-control focus-maroon" id="numberOfBlocks" name="number_of_blocks" min="1" max="10" value="1" required>
                        </div>

                        <button type="button" class="btn btn-maroon w-100 fw-bold py-2" id="generateBtn" onclick="prepareGeneration()">
                            <i class="fas fa-magic me-2"></i> Generate Schedule
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview Side -->
        <div class="col-lg-9">
            <div id="welcomePlaceholder" class="card border-0 shadow-sm text-center py-5">
                <div class="card-body py-5 text-muted">
                    <i class="fas fa-calendar-alt fa-4x mb-4 opacity-25"></i>
                    <h4>Ready to Generate</h4>
                    <p>Configure the parameters on the left and click "Generate Schedule" to begin.</p>
                </div>
            </div>

            <div id="previewSection" class="card border-0 shadow-sm d-none">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-eye me-2 text-maroon"></i> Preview
                    </h5>

                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-maroon active" id="btnGridView" onclick="switchView('grid')">
                                <i class="fas fa-th me-1"></i> Grid
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnTableView" onclick="switchView('table')">
                                <i class="fas fa-list me-1"></i> Table
                            </button>
                        </div>

                        <div id="blockSelectorContainer" class="d-none">
                            <div class="input-group input-group-sm">
                                <label class="input-group-text bg-light border-0 small fw-bold">BLOCK</label>
                                <select id="blockSelector" class="form-select border-0 bg-light focus-maroon fw-bold" onchange="switchBlock(this.value)"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="schedule-grid-container">
                        <div class="grid-wrapper">
                            <table class="table schedule-table" id="scheduleGridTable">
                                <thead>
                                    <tr>
                                        <th class="time-column">TIME</th>
                                        <th>MONDAY</th>
                                        <th>TUESDAY</th>
                                        <th>WEDNESDAY</th>
                                        <th>THURSDAY</th>
                                        <th>FRIDAY</th>
                                        <th>SATURDAY</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleGridBody">
                                    @php
                                        $timeSlots = ['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'];
                                    @endphp
                                    @foreach ($timeSlots as $time)
                                    <tr>
                                        <td class="time-column">{{ $time }}</td>
                                        @for ($day = 0; $day < 6; $day++)
                                        <td class="schedule-slot" data-day="{{ $day }}" data-time="{{ $time }}"></td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Overlay: all schedule items are absolutely positioned here -->
                            <div id="scheduleOverlay"></div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div id="tableViewContainer" class="d-none">
                    <table class="table table-hover table-sm mb-0 align-middle" id="scheduleReviewTable">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">#</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Subject</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Day</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Time</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Instructor</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Room</th>
                                <th class="px-3 py-2 small fw-bold text-uppercase text-muted">Type</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <tr><td colspan="7" class="text-center py-4 text-muted">No data yet.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-4" id="gridLegend">
                            <small class="d-flex align-items-center"><span class="legend-indicator bg-primary"></span> Lecture</small>
                            <small class="d-flex align-items-center"><span class="legend-indicator bg-success"></span> Laboratory</small>
                        </div>
                        <div id="tableSummary" class="d-none small text-muted"></div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-redo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark" onclick="printSchedule()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="button" class="btn btn-maroon fw-bold px-4" id="saveBtn" onclick="saveSchedules()">
                                <i class="fas fa-cloud-upload-alt me-1"></i> Save All Schedules
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-maroon text-white border-0">
                <h5 class="modal-title fw-bold">Confirm Generation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to generate schedules for <strong id="modalProgramName"></strong>?</p>
                <div class="bg-light p-3 rounded-3 mb-0">
                    <div class="row g-2 small">
                        <div class="col-6"><strong>Semester:</strong> <span id="modalSemester"></span></div>
                        <div class="col-6"><strong>Year Level:</strong> <span id="modalYear"></span></div>
                        <div class="col-6"><strong>Blocks:</strong> <span id="modalBlocks"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-maroon px-4 fw-bold" onclick="runGeneration()">Start Generating</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let allGeneratedSchedules = [];
    const selectedSemesterId = '{{ (string) old('semester_id', $defaultSemesterId ?? '') }}';

    const GRID_START_HOUR = 7;   // 07:00 is the first row
    const ROW_HEIGHT      = 80;  // px — must match .schedule-slot height in CSS
    const ITEM_INSET      = 3;   // px gap from cell edges

    const DAY_MAP = {
        'monday': 0,    'mon': 0,
        'tuesday': 1,   'tue': 1,
        'wednesday': 2, 'wed': 2,
        'thursday': 3,  'thu': 3,
        'friday': 4,    'fri': 4,
        'saturday': 5,  'sat': 5
    };

    async function loadSemesters(academicYearId, preferredSemesterId = '') {
        const semesterSelect = document.getElementById('semester');

        semesterSelect.innerHTML = '<option value="">Loading semesters...</option>';

        if (!academicYearId) {
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            return;
        }

        try {
            const response = await fetch(`{{ route('api.semesters.index') }}?academic_year_id=${encodeURIComponent(academicYearId)}`);
            if (!response.ok) {
                throw new Error(`Failed with status ${response.status}`);
            }

            const result = await response.json();
            let semesters = Array.isArray(result?.data) ? result.data : [];

            // Fallback: if the selected academic year has no semester records,
            // show all semesters so the dropdown remains usable.
            if (semesters.length === 0) {
                const fallbackResponse = await fetch(`{{ route('api.semesters.index') }}`);
                if (!fallbackResponse.ok) {
                    throw new Error(`Fallback failed with status ${fallbackResponse.status}`);
                }

                const fallbackResult = await fallbackResponse.json();
                semesters = Array.isArray(fallbackResult?.data) ? fallbackResult.data : [];
            }

            semesterSelect.innerHTML = '<option value="">Select Semester</option>';

            semesters.forEach((semester) => {
                const option = document.createElement('option');
                option.value = semester.id;
                option.textContent = semester.name;

                if (String(preferredSemesterId || '') === String(semester.id)) {
                    option.selected = true;
                }

                semesterSelect.appendChild(option);
            });

            if (semesters.length === 0) {
                semesterSelect.innerHTML = '<option value="">No semesters available</option>';
            }
        } catch (error) {
            console.error('Failed to load semesters:', error);
            semesterSelect.innerHTML = '<option value="">Unable to load semesters</option>';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const academicYearSelect = document.getElementById('academicYear');
        if (!academicYearSelect) {
            return;
        }

        academicYearSelect.addEventListener('change', (event) => {
            loadSemesters(event.target.value);
        });

        if (academicYearSelect.value) {
            loadSemesters(academicYearSelect.value, selectedSemesterId);
        }
    });

    /* ── helpers ─────────────────────────────────────── */
    function extractHourMinute(value) {
        const match = (value || '').toString().match(/(\d{1,2}):(\d{2})/);
        if (!match) return null;
        return { hour: parseInt(match[1], 10), minute: parseInt(match[2], 10) };
    }

    function getDurationMinutes(startValue, endValue) {
        const s = extractHourMinute(startValue);
        const e = extractHourMinute(endValue);
        if (!s || !e) return 60;
        return Math.max(30, (e.hour * 60 + e.minute) - (s.hour * 60 + s.minute));
    }

    // Strip seconds — handles "10:00:00" → "10:00"
    function fmtTime(t) {
        const m = (t || '').toString().match(/(\d{1,2}:\d{2})/);
        return m ? m[1] : (t || '');
    }

    /* ── modal / generation ──────────────────────────── */
    function prepareGeneration() {
        const form = document.getElementById('scheduleConfigForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const semesterSelect = document.getElementById('semester');
        const yearLevelSelect = document.getElementById('yearLevel');

        document.getElementById('modalProgramName').textContent = form.program_id.options[form.program_id.selectedIndex].text;
        document.getElementById('modalSemester').textContent    = semesterSelect.options[semesterSelect.selectedIndex].text;
        document.getElementById('modalYear').textContent        = yearLevelSelect.options[yearLevelSelect.selectedIndex].text;
        document.getElementById('modalBlocks').textContent      = form.number_of_blocks.value;

        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }

    async function runGeneration() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
        modal.hide();

        const btn          = document.getElementById('generateBtn');
        const originalText = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const formData = new FormData(document.getElementById('scheduleConfigForm'));
            const payload  = Object.fromEntries(formData.entries());

            const response = await fetch('{{ route("department-head.schedules.executeGeneration") }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept':       'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result.success) {
                allGeneratedSchedules = result.data.generated_schedules;
                setupPreview(allGeneratedSchedules);
            } else {
                alert('Generation failed: ' + (result.message || 'Error occurred'));
            }
        } catch (err) {
            console.error(err);
            alert('A system error occurred. Please try again.');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = originalText;
        }
    }

    function normalizeScheduleItems(scheduleEntry) {
        if (Array.isArray(scheduleEntry?.items) && scheduleEntry.items.length > 0) {
            return scheduleEntry.items;
        }

        const rows = Array.isArray(scheduleEntry?.timetable) ? scheduleEntry.timetable : [];

        return rows.map((row) => {
            return {
                day_of_week: row.day || '',
                start_time: row.start_time || row.time || '',
                end_time: row.end_time || '',
                subject_display: row.subject || row.subject_code || 'N/A',
                subject_code: row.subject_code || 'N/A',
                subject_name: row.subject_name || row.subject || 'Unknown Subject',
                instructor_name: row.faculty || 'TBA',
                room_name: row.room || 'TBA',
                class_type: row.class_type || 'lecture',
                room_type: row.room_type || '',
            };
        });
    }

    function setupPreview(schedules) {
        document.getElementById('welcomePlaceholder').classList.add('d-none');
        document.getElementById('previewSection').classList.remove('d-none');

        const selector  = document.getElementById('blockSelector');
        const container = document.getElementById('blockSelectorContainer');

        selector.innerHTML = '';
        schedules.forEach((s, i) => {
            const opt       = document.createElement('option');
            opt.value       = i;
            opt.textContent = s.block;
            selector.appendChild(opt);
        });

        container.classList.toggle('d-none', schedules.length <= 1);

        switchBlock(0);
        document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
    }

    /* ── view toggle ─────────────────────────────────── */
    function switchView(view) {
        const gridWrapper    = document.querySelector('.schedule-grid-container');
        const tableContainer = document.getElementById('tableViewContainer');
        const gridLegend     = document.getElementById('gridLegend');
        const tableSummary   = document.getElementById('tableSummary');
        const btnGrid        = document.getElementById('btnGridView');
        const btnTable       = document.getElementById('btnTableView');

        if (view === 'table') {
            gridWrapper?.classList.add('d-none');
            tableContainer.classList.remove('d-none');
            gridLegend.classList.add('d-none');
            tableSummary.classList.remove('d-none');
            btnTable.classList.replace('btn-outline-secondary', 'btn-maroon');
            btnTable.classList.add('active');
            btnGrid.classList.replace('btn-maroon', 'btn-outline-secondary');
            btnGrid.classList.remove('active');
        } else {
            gridWrapper?.classList.remove('d-none');
            tableContainer.classList.add('d-none');
            gridLegend.classList.remove('d-none');
            tableSummary.classList.add('d-none');
            btnGrid.classList.replace('btn-outline-secondary', 'btn-maroon');
            btnGrid.classList.add('active');
            btnTable.classList.replace('btn-maroon', 'btn-outline-secondary');
            btnTable.classList.remove('active');
        }
    }

    /* ── core render ─────────────────────────────────── */
    function switchBlock(idx) {
        idx = parseInt(idx, 10);

        /* ======================================================
           GRID VIEW — overlay-based absolute positioning
           ====================================================== */
        const overlay = document.getElementById('scheduleOverlay');
        overlay.innerHTML = '';

        const items = normalizeScheduleItems(allGeneratedSchedules[idx] || {});

        // Measure the table to get column positions
        const table       = document.getElementById('scheduleGridTable');
        const thead       = table.querySelector('thead');
        const headerCells = thead.querySelectorAll('th'); // [TIME, MON, TUE, WED, THU, FRI, SAT]
        const theadHeight = thead.offsetHeight;

        // Build cumulative left offsets for each day column (0 = Mon … 5 = Sat)
        const colLefts  = [];
        const colWidths = [];
        let cumLeft     = headerCells[0].offsetWidth; // skip TIME column
        for (let d = 0; d < 6; d++) {
            const cell   = headerCells[d + 1];
            colLefts[d]  = cumLeft;
            colWidths[d] = cell ? cell.offsetWidth : 0;
            cumLeft     += colWidths[d];
        }

        items.forEach((item, i) => {
            // ── resolve day index ──────────────────────────────
            const dayRaw = (item.day_of_week || '').toLowerCase().trim();
            let dayIdx   = DAY_MAP[dayRaw] ?? DAY_MAP[dayRaw.substring(0, 3)] ?? null;
            if (dayIdx === null) {
                for (const [key, val] of Object.entries(DAY_MAP)) {
                    if (dayRaw.includes(key)) { dayIdx = val; break; }
                }
            }
            if (dayIdx === null) {
                console.warn(`Item ${i}: Unrecognized day "${item.day_of_week}"`);
                return;
            }

            // ── resolve times ──────────────────────────────────
            const startParts = extractHourMinute(item.start_time || '');
            const endParts   = extractHourMinute(item.end_time   || '');
            if (!startParts || !endParts) {
                console.warn(`Item ${i}: Cannot parse time "${item.start_time}" / "${item.end_time}"`);
                return;
            }

            const durationMins = getDurationMinutes(item.start_time, item.end_time);
            const durationHrs  = durationMins / 60;
            const startDisplay = fmtTime(item.start_time);
            const endDisplay   = fmtTime(item.end_time);

            // ── pixel geometry ─────────────────────────────────
            // top = thead height + rows above + minute offset within starting row + inset
            const rowIndex = startParts.hour - GRID_START_HOUR;
            const topPx    = theadHeight
                           + rowIndex * ROW_HEIGHT
                           + (startParts.minute / 60) * ROW_HEIGHT
                           + ITEM_INSET;

            // height = full duration in pixels minus only the top inset (not both sides),
            // so the block visually fills exactly to the end-time row boundary
            const heightPx = Math.max(24, (durationMins / 60) * ROW_HEIGHT - ITEM_INSET);
            const leftPx   = (colLefts[dayIdx]  ?? 0) + ITEM_INSET;
            const widthPx  = (colWidths[dayIdx] ?? 120) - ITEM_INSET * 2;

            // ── type detection ─────────────────────────────────
            const classType = (item.class_type || '').toLowerCase();
            const isLab     = classType === 'laboratory'
                           || (item.room_type || '').toLowerCase().includes('lab')
                           || (item.room_name || '').toLowerCase().includes('lab');

            // ── build element ──────────────────────────────────
            const div         = document.createElement('div');
            div.className     = `schedule-item${isLab ? ' lab' : ''}`;
            div.style.cssText = `top:${topPx}px; left:${leftPx}px; width:${widthPx}px; height:${heightPx}px;`;
            div.title         = `${item.subject_display || item.subject_code} — ${item.instructor_name} (${item.room_name}) @ ${startDisplay}–${endDisplay}`;

            div.innerHTML = `
                <div class="fw-bold" style="font-size:0.72rem; line-height:1.2; margin-bottom:2px;">${item.subject_display || item.subject_code}</div>
                <div style="opacity:0.9; font-size:0.62rem; margin-bottom:2px;">${item.instructor_name}</div>
                <div style="opacity:0.8; font-size:0.58rem;"><i class="fas fa-door-open me-1"></i>${item.room_name}</div>
                ${durationHrs >= 1
                    ? `<div style="margin-top:auto; padding-top:3px; border-top:1px solid rgba(255,255,255,0.25); font-size:0.52rem; opacity:0.75;">${startDisplay} – ${endDisplay}</div>`
                    : ''}
            `;
            overlay.appendChild(div);
        });

        /* ======================================================
           TABLE VIEW
           ====================================================== */
        const tbody = document.getElementById('scheduleTableBody');

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No items generated.</td></tr>';
        } else {
            const shortDay = { Monday:'Mon', Tuesday:'Tue', Wednesday:'Wed', Thursday:'Thu', Friday:'Fri', Saturday:'Sat', Sunday:'Sun' };

            tbody.innerHTML = items.map((item, i) => {
                const timeDisplay = `${fmtTime(item.start_time)} – ${fmtTime(item.end_time)}`;
                const dayShort    = shortDay[item.day_of_week] || item.day_of_week;
                const classType   = (item.class_type || '').toLowerCase();
                const isLab       = classType === 'laboratory'
                                 || (item.room_type || '').toLowerCase().includes('lab')
                                 || (item.room_name || '').toLowerCase().includes('lab');
                const typeBadge   = isLab
                    ? `<span class="badge lab-badge py-1 px-2" style="font-size:0.6rem;">Lab</span>`
                    : `<span class="badge lecture-badge py-1 px-2" style="font-size:0.6rem;">Lecture</span>`;

                return `
                <tr>
                    <td class="px-3 text-muted text-center" style="font-size:0.65rem;">${i + 1}</td>
                    <td class="px-3">
                        <div class="fw-bold text-dark mb-0 schedule-table-subject" style="font-size:0.8rem;">${item.subject_display || item.subject_code}</div>
                        <div class="text-muted text-truncate schedule-table-subject" style="font-size:0.65rem;">${item.subject_name}</div>
                    </td>
                    <td class="px-3 fw-medium text-secondary" style="font-size:0.75rem;">${dayShort}</td>
                    <td class="px-3 text-dark fw-semibold" style="font-size:0.75rem; white-space:nowrap;">${timeDisplay}</td>
                    <td class="px-3 small text-muted schedule-table-instructor text-truncate">${item.instructor_name}</td>
                    <td class="px-3">
                        <span class="badge bg-light text-dark border fw-normal" style="font-size:0.7rem;">
                            <i class="fas fa-door-open me-1 opacity-50"></i>${item.room_name}
                        </span>
                    </td>
                    <td class="px-3 text-center">${typeBadge}</td>
                </tr>`;
            }).join('');
        }

        document.getElementById('tableSummary').textContent =
            `${items.length} class${items.length !== 1 ? 'es' : ''} assigned`;
    }

    /* ── print ───────────────────────────────────────── */
    function printSchedule() {
        if (!allGeneratedSchedules.length) { alert('Please generate a schedule first.'); return; }

        const idx          = parseInt(document.getElementById('blockSelector')?.value ?? 0);
        const blockData    = allGeneratedSchedules[idx];
        const blockName    = blockData?.block ?? 'Block 1';
        const isTableView  = !document.getElementById('tableViewContainer').classList.contains('d-none');

        const programSel   = document.getElementById('programSelect');
        const semesterSel  = document.getElementById('semester');
        const yearSel      = document.getElementById('yearLevel');
        const programName  = programSel?.options[programSel.selectedIndex]?.text   ?? '';
        const semesterName = semesterSel?.options[semesterSel.selectedIndex]?.text ?? '';
        const yearName     = yearSel?.options[yearSel.selectedIndex]?.text          ?? '';

        const contentEl    = isTableView
            ? document.getElementById('tableViewContainer')
            : document.querySelector('.schedule-grid-container');
        const contentHTML  = contentEl?.innerHTML ?? '<p>No content to print.</p>';

        const win = window.open('', '_blank', 'width=1100,height=800');
        win.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Schedule – ${blockName}</title>
<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:white; color:#1a1a1a; padding:24px; }
    .print-header { border-bottom:3px solid #800000; padding-bottom:14px; margin-bottom:20px; }
    .print-header h1 { font-size:1.15rem; font-weight:700; color:#800000; margin-bottom:4px; }
    .print-header .meta { display:flex; gap:24px; font-size:0.8rem; color:#555; margin-top:6px; }
    .print-header .meta span strong { color:#1a1a1a; }
    /* Grid */
    .grid-wrapper { position:relative; }
    table.schedule-table { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.schedule-table th { background:#f0f0f0; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; padding:8px 4px; text-align:center; border:1px solid #ccc; }
    .time-column { width:68px; background:#f8f8f8; font-size:0.65rem; font-weight:600; color:#666; text-align:center; vertical-align:middle; border:1px solid #ccc; padding:4px; }
    .schedule-slot { height:80px; border:1px solid #ddd; padding:0; vertical-align:top; overflow:hidden; }
    #scheduleOverlay { position:absolute; top:0; left:0; right:0; pointer-events:none; }
    .schedule-item { position:absolute; background:#007bff; color:white; padding:5px 6px; border-radius:3px; font-size:0.58rem; border-left:3px solid rgba(255,255,255,0.4); -webkit-print-color-adjust:exact; print-color-adjust:exact; display:flex; flex-direction:column; overflow:hidden; box-sizing:border-box; }
    .schedule-item.lab { background:#28a745; }
    /* Table */
    table.table { width:100%; border-collapse:collapse; font-size:0.75rem; }
    table.table th { background:#f8f8f8; padding:10px; text-align:left; font-size:0.65rem; font-weight:700; text-transform:uppercase; color:#444; border-bottom:2px solid #ddd; }
    table.table td { padding:8px 10px; border-bottom:1px solid #eee; vertical-align:middle; color:#333; }
    table.table tr:nth-child(even) td { background:#fafafa; }
    .badge { font-size:0.6rem; padding:2px 5px; border-radius:3px; font-weight:600; text-transform:uppercase; }
    .badge-lecture { background:#f0f7ff; color:#0056b3; border:1px solid #cce5ff; }
    .badge-lab     { background:#f0fff4; color:#1e7e34; border:1px solid #d4edda; }
    .print-footer { margin-top:20px; font-size:0.72rem; color:#999; text-align:right; border-top:1px solid #eee; padding-top:10px; }
    @media print { body { padding:0; } }
</style>
</head>
<body>
<div class="print-header">
    <h1>📅 Class Schedule – ${blockName}</h1>
    <div class="meta">
        <span><strong>Program:</strong> ${programName}</span>
        <span><strong>Year Level:</strong> ${yearName}</span>
        <span><strong>Semester:</strong> ${semesterName}</span>
        <span><strong>View:</strong> ${isTableView ? 'Table' : 'Weekly Grid'}</span>
    </div>
</div>
${contentHTML
    .replace(/class="badge bg-success[^"]*"/g, 'class="badge badge-lab"')
    .replace(/class="badge bg-primary[^"]*"/g, 'class="badge badge-lecture"')}
<div class="print-footer">Sorsu Scheduling System &mdash; Printed on ${new Date().toLocaleString()}</div>
</body>
</html>`);
        win.document.close();
        setTimeout(() => { win.focus(); win.print(); }, 400);
    }

    /* ── save ────────────────────────────────────────── */
    async function saveSchedules() {
        if (!confirm('Finalize and save all generated schedules?')) return;

        const btn      = document.getElementById('saveBtn');
        const original = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        try {
            let count = 0;
            for (const s of allGeneratedSchedules) {
                const res = await fetch(`/department-head/schedules/${s.schedule_id}/finalize`, {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });

                const contentType = res.headers.get('content-type') || '';
                const payload = contentType.includes('application/json')
                    ? await res.json()
                    : { success: false, message: await res.text() };

                if (!res.ok || !payload.success) {
                    throw new Error(payload.message || `Failed to save schedule ${s.schedule_id}.`);
                }

                count++;
            }
            alert(`Successfully saved ${count} schedules.`);
            window.location.href = '{{ route("department-head.schedules.index") }}';
        } catch (err) {
            alert('Error saving: ' + err.message);
        } finally {
            btn.disabled  = false;
            btn.innerHTML = original;
        }
    }
</script>
@endpush