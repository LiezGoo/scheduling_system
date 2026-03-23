@extends('layouts.app')

@section('page-title', 'Generate Schedule')

@push('styles')
<style>
    :root {
        --maroon: #800000;
        --maroon-dark: #600000;
        --maroon-light: rgba(128, 0, 0, 0.05);
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

    #tableViewContainer {
        overflow-x: auto;
    }

    #universityTimetable {
        width: 100%;
        min-width: 980px;
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: collapse;
    }

    #universityTimetable th,
    #universityTimetable td {
        vertical-align: middle;
        border: 1px solid #d8dce0;
        padding: 10px 8px;
    }

    .uni-meta-row th {
        background: #f8f9fb;
        color: #4b5563;
        font-size: 0.72rem;
        letter-spacing: 0.2px;
        text-transform: uppercase;
        font-weight: 700;
        text-align: center;
    }

    .uni-header-row th {
        background: #edf1f5;
        color: #1f2937;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 700;
        text-align: center;
    }

    .uni-time-col {
        width: 120px;
        background: #f8f9fa;
        color: #4b5563;
        font-size: 0.74rem;
        font-weight: 700;
        text-align: center;
    }

    .uni-break-row td {
        background: #e9ecef;
        color: #374151;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        text-align: center;
        padding: 9px 8px;
    }

    .uni-cell {
        text-align: center;
        font-size: 0.72rem;
        min-height: 68px;
    }

    .uni-cell-empty {
        background: #fcfcfd;
    }

    .uni-cell-empty .placeholder {
        color: #c0c7cf;
        font-size: 0.68rem;
        letter-spacing: 0.3px;
    }

    .uni-subject {
        font-weight: 800;
        font-size: 0.76rem;
        color: #1f2937;
        margin-bottom: 2px;
    }

    .uni-faculty,
    .uni-room,
    .uni-time {
        display: block;
        color: #374151;
        font-size: 0.67rem;
        line-height: 1.25;
    }

    .uni-lecture {
        background: #e9f4ff;
    }

    .uni-lab {
        background: #eaf8ee;
    }

    .uni-nstp {
        background: #fff0dd;
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

                    <form id="scheduleConfigForm" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label-custom">Program</label>
                            <select class="form-select focus-maroon" id="programSelect" name="program_id" required>
                                <option value="" selected>Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" {{ (string) ($defaultProgramId ?? '') === (string) $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Academic Year</label>
                            <select class="form-select focus-maroon" id="academicYear" name="academic_year_id" required>
                                <option value="" selected>Select Academic Year</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ (string) ($defaultAcademicYearId ?? '') === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Semester</label>
                            <select class="form-select focus-maroon" id="semester" name="semester_id" required>
                                <option value="" selected>Select Semester</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ (string) ($defaultSemesterId ?? '') === (string) $semester->id ? 'selected' : '' }}>{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Year Level</label>
                            <select class="form-select focus-maroon" id="yearLevel" name="year_level_id" required>
                                <option value="" selected>Select Year Level</option>
                                @foreach ($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel->id }}" {{ (string) ($defaultYearLevelId ?? '') === (string) $yearLevel->id ? 'selected' : '' }}>
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
                        <div id="blockSelectorContainer" class="d-none">
                            <div class="input-group input-group-sm">
                                <label class="input-group-text bg-light border-0 small fw-bold">BLOCK</label>
                                <select id="blockSelector" class="form-select border-0 bg-light focus-maroon fw-bold" onchange="switchBlock(this.value)"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div id="tableViewContainer">
                    <table class="table mb-0" id="universityTimetable">
                        <thead>
                            <tr class="uni-meta-row">
                                <th colspan="7">
                                    <span class="me-3">Flag Raising: Monday 7:15 AM</span>
                                    <span>Flag Lowering: Friday 4:45 PM</span>
                                </th>
                            </tr>
                            <tr class="uni-header-row">
                                <th class="uni-time-col">Time</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                            </tr>
                        </thead>
                        <tbody id="universityTimetableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No data yet.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="tableSummary" class="small text-muted"></div>
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
    const selectedSemesterId = '{{ (string) ($defaultSemesterId ?? '') }}';

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
        const form = document.getElementById('scheduleConfigForm');
        if (form) {
            form.reset();
        }

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

    window.addEventListener('pageshow', () => {
        const form = document.getElementById('scheduleConfigForm');
        const academicYearSelect = document.getElementById('academicYear');
        if (!form || !academicYearSelect) {
            return;
        }

        form.reset();
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
                showSystemModal('Generation failed: ' + (result.message || 'Error occurred'), 'error');
            }
        } catch (err) {
            console.error(err);
            showSystemModal('A system error occurred. Please try again.', 'error');
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

    const UNIVERSITY_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const TABLE_SLOT_START_HOUR = 7;
    const TABLE_SLOT_END_HOUR = 18;
    const TABLE_SLOT_MINUTES = 60;
    const NOON_BREAK_START = '12:00';
    const NOON_BREAK_END = '13:00';

    function minutesFromHHMM(value) {
        const parsed = extractHourMinute(value);
        if (!parsed) {
            return null;
        }

        return parsed.hour * 60 + parsed.minute;
    }

    function overlaps(startA, endA, startB, endB) {
        return startA < endB && endA > startB;
    }

    function toHHMM(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
    }

    function formatTimeRange(start, end) {
        return `${start}-${end}`;
    }

    function shouldRenderNoonBreak(scheduleData = []) {
        const noonStart = minutesFromHHMM(NOON_BREAK_START);
        const noonEnd = minutesFromHHMM(NOON_BREAK_END);

        if (noonStart === null || noonEnd === null) {
            return false;
        }

        return !(scheduleData || []).some((item) => {
            const start = minutesFromHHMM(item.start_time);
            const end = minutesFromHHMM(item.end_time);
            if (start === null || end === null) {
                return false;
            }

            return overlaps(start, end, noonStart, noonEnd);
        });
    }

    function generateTimeSlots(scheduleData = []) {
        const slots = [];
        const dayStart = TABLE_SLOT_START_HOUR * 60;
        const dayEnd = TABLE_SLOT_END_HOUR * 60;
        const renderNoonBreak = shouldRenderNoonBreak(scheduleData);

        for (let cursor = dayStart; cursor < dayEnd; cursor += TABLE_SLOT_MINUTES) {
            const start = toHHMM(cursor);
            const end = toHHMM(cursor + TABLE_SLOT_MINUTES);

            if (renderNoonBreak && start === NOON_BREAK_START && end === NOON_BREAK_END) {
                slots.push({
                    type: 'break',
                    label: 'NOON BREAK',
                    start,
                    end,
                });
                continue;
            }

            slots.push({
                type: 'slot',
                key: start,
                start,
                end,
                label: formatTimeRange(start, end),
            });
        }

        return slots;
    }

    function mapSchedule(scheduleData) {
        const map = {};

        (scheduleData || []).forEach((item) => {
            const day = item?.day_of_week || '';
            const start = fmtTime(item?.start_time || '');

            if (!day || !start) {
                return;
            }

            const key = `${day}_${start}`;
            if (!map[key]) {
                map[key] = item;
            }
        });

        return map;
    }

    function calculateRowSpan(item, slotRows) {
        const start = minutesFromHHMM(item.start_time);
        const end = minutesFromHHMM(item.end_time);
        if (start === null || end === null || end <= start) {
            return 1;
        }

        let touched = 0;
        slotRows.forEach((slot) => {
            const slotStart = minutesFromHHMM(slot.start);
            const slotEnd = minutesFromHHMM(slot.end);
            if (slotStart === null || slotEnd === null) {
                return;
            }

            if (overlaps(start, end, slotStart, slotEnd)) {
                touched++;
            }
        });

        return Math.max(1, touched);
    }

    function cellTypeClass(item) {
        const subjectText = `${item.subject_display || ''} ${item.subject_name || ''} ${item.subject_code || ''}`.toLowerCase();
        if (subjectText.includes('nstp')) {
            return 'uni-nstp';
        }

        const classType = (item.class_type || '').toLowerCase();
        const isLab = classType === 'laboratory'
            || (item.room_type || '').toLowerCase().includes('lab')
            || (item.room_name || '').toLowerCase().includes('lab');

        return isLab ? 'uni-lab' : 'uni-lecture';
    }

    function renderUniversityTimetable(items) {
        const tbody = document.getElementById('universityTimetableBody');
        if (!tbody) {
            return;
        }

        const timeRows = generateTimeSlots(items);
        const slotRows = timeRows.filter((row) => row.type === 'slot');
        const scheduleMap = mapSchedule(items);
        const occupied = Object.fromEntries(UNIVERSITY_DAYS.map((day) => [day, 0]));

        const sortedItems = [...items].sort((a, b) => {
            const dayDiff = UNIVERSITY_DAYS.indexOf(a.day_of_week) - UNIVERSITY_DAYS.indexOf(b.day_of_week);
            if (dayDiff !== 0) {
                return dayDiff;
            }

            return (minutesFromHHMM(a.start_time) || 0) - (minutesFromHHMM(b.start_time) || 0);
        });

        let html = '';

        timeRows.forEach((row) => {
            if (row.type === 'break') {
                html += `
                    <tr class="uni-break-row">
                        <td colspan="7"><strong>${row.label}</strong></td>
                    </tr>
                `;
                return;
            }

            html += `<tr><td class="uni-time-col">${row.label}</td>`;

            UNIVERSITY_DAYS.forEach((day) => {
                if (occupied[day] > 0) {
                    occupied[day] -= 1;
                    return;
                }

                const key = `${day}_${row.start}`;
                const item = scheduleMap[key];

                if (item) {
                    const subjectDisplay = item.subject_display || item.subject_code || 'N/A';
                    const faculty = item.instructor_name || 'TBA';
                    const room = item.room_name || 'TBA';
                    const timeLabel = `${fmtTime(item.start_time)} - ${fmtTime(item.end_time)}`;
                    const rowSpan = calculateRowSpan(item, slotRows);
                    occupied[day] = Math.max(0, rowSpan - 1);

                    html += `
                        <td class="uni-cell ${cellTypeClass(item)}" rowspan="${rowSpan}">
                            <div class="uni-subject">${subjectDisplay}</div>
                            <span class="uni-faculty">${faculty}</span>
                            <span class="uni-room">${room}</span>
                            <span class="uni-time">${timeLabel}</span>
                        </td>
                    `;
                } else {
                    html += '<td class="uni-cell uni-cell-empty"><span class="placeholder">-</span></td>';
                }
            });

            html += '</tr>';
        });

        tbody.innerHTML = html;
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

    /* ── render ──────────────────────────────────────── */
    function switchBlock(idx) {
        idx = parseInt(idx, 10);

        const items = normalizeScheduleItems(allGeneratedSchedules[idx] || {});
        renderUniversityTimetable(items);

        document.getElementById('tableSummary').textContent =
            `${items.length} class${items.length !== 1 ? 'es' : ''} mapped to university timetable`;
    }

    /* ── print ───────────────────────────────────────── */
    function printSchedule() {
        if (!allGeneratedSchedules.length) { showSystemModal('Please generate a schedule first.', 'warning'); return; }

        const idx          = parseInt(document.getElementById('blockSelector')?.value ?? 0);
        const blockData    = allGeneratedSchedules[idx];
        const blockName    = blockData?.block ?? 'Block 1';

        const programSel   = document.getElementById('programSelect');
        const semesterSel  = document.getElementById('semester');
        const yearSel      = document.getElementById('yearLevel');
        const programName  = programSel?.options[programSel.selectedIndex]?.text   ?? '';
        const semesterName = semesterSel?.options[semesterSel.selectedIndex]?.text ?? '';
        const yearName     = yearSel?.options[yearSel.selectedIndex]?.text          ?? '';

        const contentEl    = document.getElementById('tableViewContainer');
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
        <span><strong>View:</strong> Table</span>
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
        showConfirmModal('Finalize and save all generated schedules?', async function() {
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
                showSystemModal(`Successfully saved ${count} schedules.`, 'success');
                window.location.href = '{{ route("department-head.schedules.index") }}';
            } catch (err) {
                showSystemModal('Error saving: ' + err.message, 'error');
            } finally {
                btn.disabled  = false;
                btn.innerHTML = original;
            }
        }, {
            title: 'Finalize Schedules',
            btnClass: 'btn-danger',
            btnText: '<i class="fa-solid fa-check me-1"></i>Yes, Continue'
        });
    }
</script>
@endpush