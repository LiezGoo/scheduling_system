/**
 * Faculty Load Management - UI Interactions
 * Handles modals, forms, filters, and AJAX operations
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================
    // MODAL & FORM MANAGEMENT
    // =========================================

    const inferredBase = window.location.pathname.includes('/program-head')
        ? '/program-head/faculty-load'
        : '/admin/faculty-load';
    const baseUrl = document.querySelector('[data-faculty-load-base]')?.dataset.facultyLoadBase || inferredBase;

    const assignModal = new bootstrap.Modal(document.getElementById('assignFacultyLoadModal'), {
        backdrop: 'static',
        keyboard: false,
    });

    const editModal = new bootstrap.Modal(document.getElementById('editFacultyLoadModal'), {
        backdrop: 'static',
        keyboard: false,
    });

    const viewModal = new bootstrap.Modal(document.getElementById('viewFacultyLoadModal'));
    const removeModal = new bootstrap.Modal(document.getElementById('removeFacultyLoadModal'));
    const overloadModal = new bootstrap.Modal(document.getElementById('overloadWarningModal'));
    const assignConfirmationModalEl = document.getElementById('assignConfirmationModal');
    const assignConfirmationModal = assignConfirmationModalEl
        ? new bootstrap.Modal(assignConfirmationModalEl, { backdrop: 'static', keyboard: false })
        : null;

    let pendingForceAction = null;

    const confirmForceAssignBtn = document.getElementById('confirmForceAssignBtn');
    if (confirmForceAssignBtn) {
        confirmForceAssignBtn.addEventListener('click', () => {
            overloadModal.hide();
            if (pendingForceAction) {
                const action = pendingForceAction;
                pendingForceAction = null;
                action();
            }
        });
    }

    // =========================================
    // TABLE ACTION HANDLERS
    // =========================================

    document.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', function () {
            const action = this.dataset.action;
            const id = this.dataset.id;

            switch (action) {
                case 'view':
                    handleViewAction(id);
                    break;
                case 'edit':
                    handleEditAction(id);
                    break;
                case 'remove':
                    handleRemoveAction(id);
                    break;
            }
        });
    });

    // View Faculty Load
    function handleViewAction(id) {
        fetch(`${baseUrl}/${id}/details`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                populateViewModal(data);
                viewModal.show();
            })
            .catch((error) => {
                console.error('Error loading details:', error);
                showAlert('error', 'Failed to load faculty load details.');
            });
    }

    function populateViewModal(data) {
        document.getElementById('viewFacultyName').textContent = data.faculty.full_name;
        document.getElementById('viewFacultyRole').textContent = data.faculty.role_label;
        const contractType = data.faculty.contract_type || 'Unspecified';
        const contractEl = document.getElementById('viewContractType');
        if (contractEl) {
            contractEl.textContent = contractType.charAt(0).toUpperCase() + contractType.slice(1);
        }
        document.getElementById('viewSubjectName').textContent = data.subject.subject_name;
        document.getElementById('viewSubjectCode').textContent = data.subject.subject_code;
        const department = data.department || {};
        const departmentName = department.department_name || 'N/A';
        const departmentEl = document.getElementById('viewDepartmentName');
        if (departmentEl) {
            departmentEl.textContent = departmentName;
        }

        const programEl = document.getElementById('viewProgramName');
        if (programEl) {
            programEl.textContent = data.program?.program_name || 'N/A';
        }

        const academicYearEl = document.getElementById('viewAcademicYear');
        if (academicYearEl) {
            academicYearEl.textContent = data.academic_year?.name || 'N/A';
        }

        const termEl = document.getElementById('viewTerm');
        if (termEl) {
            const yearLevel = data.year_level ? `Year ${data.year_level}` : 'Year —';
            const block = data.block_section ? `Block ${data.block_section}` : 'Block —';
            termEl.textContent = `${data.semester || '—'} • ${yearLevel} • ${block}`;
        }
        document.getElementById('viewLectureHours').textContent = data.lecture_hours || 0;
        document.getElementById('viewLabHours').textContent = data.lab_hours || 0;
        document.getElementById('viewComputedUnits').textContent = data.total_hours || 0;
        const limitsEl = document.getElementById('viewLoadLimits');
        if (limitsEl) {
            const maxLecture = data.limits?.max_lecture_hours ?? '—';
            const maxLab = data.limits?.max_lab_hours ?? '—';
            limitsEl.textContent = `Lecture ${maxLecture} hrs • Lab ${maxLab} hrs`;
        }

        const currentLoadEl = document.getElementById('viewCurrentLoad');
        if (currentLoadEl) {
            const currentLecture = data.current_load?.total_lecture_hours ?? 0;
            const currentLab = data.current_load?.total_lab_hours ?? 0;
            const currentTotal = (currentLecture + currentLab) || 0;
            currentLoadEl.textContent = `Lecture ${currentLecture} hrs • Lab ${currentLab} hrs • Total ${currentTotal} hrs`;
        }
        const statusBadge = document.getElementById('viewStatus');
        if (statusBadge) {
            const maxLecture = data.limits?.max_lecture_hours ?? null;
            const maxLab = data.limits?.max_lab_hours ?? null;
            const currentLecture = data.current_load?.total_lecture_hours ?? 0;
            const currentLab = data.current_load?.total_lab_hours ?? 0;
            const nearRatio = 0.85;

            let statusLabel = 'Normal';
            let statusClass = 'bg-success';

            const isOverLecture = maxLecture !== null && currentLecture > maxLecture;
            const isOverLab = maxLab !== null && currentLab > maxLab;
            const isNearLecture = maxLecture !== null && maxLecture > 0 && currentLecture / maxLecture >= nearRatio;
            const isNearLab = maxLab !== null && maxLab > 0 && currentLab / maxLab >= nearRatio;

            if (isOverLecture || isOverLab) {
                statusLabel = 'Overload';
                statusClass = 'bg-danger';
            } else if (isNearLecture || isNearLab) {
                statusLabel = 'Near Limit';
                statusClass = 'bg-warning text-dark';
            }

            statusBadge.className = `badge ${statusClass}`;
            statusBadge.textContent = statusLabel;
        }
        document.getElementById('viewAssignedDate').textContent = formatDate(data.created_at);

        // Set edit button functionality
        document.getElementById('viewEditBtn').onclick = function () {
            viewModal.hide();
            handleEditAction(data.id);
        };
    }

    // Edit Faculty Load
    function handleEditAction(id) {
        fetch(`${baseUrl}/${id}/details`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                populateEditModal(data);
                editModal.show();
            })
            .catch((error) => {
                console.error('Error loading details:', error);
                showAlert('error', 'Failed to load faculty load details.');
            });
    }

    function populateEditModal(data) {
        document.getElementById('editFacultyLoadId').value = data.id;
        document.getElementById('editFacultyDisplay').value = `${data.faculty.full_name} (${data.faculty.school_id})`;
        document.getElementById('editSubjectDisplay').value = `${data.subject.subject_code} - ${data.subject.subject_name}`;
        document.getElementById('editLectureHours').value = data.lecture_hours || 0;
        document.getElementById('editLabHours').value = data.lab_hours || 0;
        // Update total hours display
        const units = calculateTeachingUnits(data.lecture_hours || 0, data.lab_hours || 0);
        document.getElementById('editComputedUnits').textContent = units;
    }

    // Remove Faculty Load
    function handleRemoveAction(id) {
        fetch(`${baseUrl}/${id}/details`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                document.getElementById('removeFacultyLoadName').textContent = `${data.faculty.full_name}`;
                document.getElementById('removeFacultyLoadSubject').textContent = `${data.subject.subject_code}`;
                removeModal.show();

                // Set the confirm button action
                document.getElementById('confirmRemoveBtn').onclick = function () {
                    confirmRemove(id);
                };
            })
            .catch((error) => {
                console.error('Error loading details:', error);
                showAlert('error', 'Failed to load faculty load details.');
            });
    }

    function confirmRemove(id) {
        const confirmBtn = document.getElementById('confirmRemoveBtn');
        const originalContent = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Removing...';

        fetch(`${baseUrl}/remove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ faculty_load_id: id }),
        })
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then((data) => {
                removeModal.hide();
                showAlert('success', 'Faculty load assignment removed successfully.');
                setTimeout(() => location.reload(), 1500);
            })
            .catch((error) => {
                console.error('Error removing assignment:', error);
                showAlert('error', 'Failed to remove faculty load assignment.');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalContent;
            });
    }

    // =========================================
    // FILTER FUNCTIONALITY
    // =========================================

    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const filtersSpinner = document.getElementById('filtersSpinner');

    // Auto-submit filters on change
    filterForm.querySelectorAll('input[type="text"], select').forEach((input) => {
        input.addEventListener('change', function () {
            submitFilters();
        });

        // Debounced search input
        if (input.type === 'text') {
            let timeout;
            input.addEventListener('keyup', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => submitFilters(), 500);
            });
        }
    });

    function submitFilters() {
        filtersSpinner.classList.remove('d-none');
        filterForm.submit();
    }

    // Clear filters
    clearFiltersBtn.addEventListener('click', function (e) {
        e.preventDefault();
        filterForm.reset();
        filtersSpinner.classList.remove('d-none');
        filterForm.submit();
    });

    // =========================================
    // PAGINATION & PER-PAGE
    // =========================================

    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', 1); // Reset to first page
            window.location.href = url.toString();
        });
    }

    // =========================================
    // FORM SUBMISSIONS
    // =========================================

    const assignForm = document.getElementById('assignFacultyLoadForm');
    const editForm = document.getElementById('editFacultyLoadForm');
    const assignSubmitBtn = document.getElementById('assignSubmitBtn');
    const confirmAssignSubjectsBtn = document.getElementById('confirmAssignSubjectsBtn');

    const assignFaculty = document.getElementById('assignFaculty');
    const assignProgram = document.getElementById('assignProgram');
    const assignAcademicYear = document.getElementById('assignAcademicYear');
    const assignSemester = document.getElementById('assignSemester');
    const assignYearLevel = document.getElementById('assignYearLevel');
    const assignBlockSection = document.getElementById('assignBlockSection');
    const assignSubject = document.getElementById('assignSubject');
    const assignSelectAllSubjects = document.getElementById('assignSelectAllSubjects');
    const assignSubjectsTableBody = document.getElementById('assignSubjectsTableBody');

    const isBulkAssignMode = !!assignSubjectsTableBody;
    const isOneByOneAssignMode = !isBulkAssignMode && !!assignSubject;

    let assignableSubjects = [];
    let loadSummary = {
        current_lecture_hours: 0,
        current_lab_hours: 0,
        contract_type: 'unspecified',
        max_lecture_hours: null,
        max_lab_hours: null,
    };

    const getAssignmentContext = () => ({
        faculty_id: assignFaculty?.value || '',
        program_id: assignProgram?.value || '',
        academic_year_id: assignAcademicYear?.value || '',
        semester: assignSemester?.value || '',
        year_level: assignYearLevel?.value || '',
        block_section: assignBlockSection?.value?.trim() || '',
    });

    const hasCompleteAssignmentContext = () => {
        const context = getAssignmentContext();
        return Object.values(context).every((value) => value !== '');
    };

    const toTitleCase = (value) => {
        if (!value) return 'N/A';
        return value.toString().charAt(0).toUpperCase() + value.toString().slice(1);
    };

    function getSelectedSubjectRows() {
        const rows = Array.from(document.querySelectorAll('#assignSubjectsTableBody tr[data-subject-id]'));
        return rows
            .filter((row) => row.querySelector('.assign-subject-checkbox')?.checked)
            .map((row) => ({
                rowIndex: parseInt(row.dataset.rowIndex, 10),
                subject_id: parseInt(row.dataset.subjectId, 10),
                block: row.querySelector('.assign-row-block')?.value?.trim() || assignBlockSection?.value?.trim() || '',
                lecture_hours: parseInt(row.dataset.lectureHours || '0', 10),
                lab_hours: parseInt(row.dataset.labHours || '0', 10),
            }));
    }

    function renderAssignableSubjectsTable(subjects) {
        if (!assignSubjectsTableBody) return;

        if (!Array.isArray(subjects) || subjects.length === 0) {
            assignSubjectsTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No subjects found for selected program, semester, and year level.</td>
                </tr>
            `;
            if (assignSelectAllSubjects) assignSelectAllSubjects.checked = false;
            updateBulkLoadSummary();
            return;
        }

        const blockDefault = assignBlockSection?.value?.trim() || '';

        assignSubjectsTableBody.innerHTML = subjects.map((subject, index) => {
            const disabled = subject.already_assigned ? 'disabled' : '';
            const errorText = subject.error ? `<span class="text-danger small">${subject.error}</span>` : '';

            return `
                <tr data-row-index="${index}" data-subject-id="${subject.subject_id}" data-lecture-hours="${subject.lecture_hours}" data-lab-hours="${subject.lab_hours}">
                    <td>
                        <input type="checkbox" class="form-check-input assign-subject-checkbox" ${disabled}>
                    </td>
                    <td class="fw-semibold">${subject.subject_code}</td>
                    <td>${subject.subject_name}</td>
                    <td class="text-center">${subject.lecture_hours}</td>
                    <td class="text-center">${subject.lab_hours}</td>
                    <td class="text-center fw-semibold">${subject.total_hours}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm assign-row-block" value="${blockDefault}" ${disabled}>
                    </td>
                    <td class="assign-row-error">${errorText}</td>
                </tr>
            `;
        }).join('');

        if (assignSelectAllSubjects) {
            assignSelectAllSubjects.checked = false;
        }

        assignSubjectsTableBody.querySelectorAll('.assign-subject-checkbox, .assign-row-block').forEach((element) => {
            element.addEventListener('change', updateBulkLoadSummary);
            element.addEventListener('input', updateBulkLoadSummary);
        });

        updateBulkLoadSummary();
    }

    function resetSubjectRowErrors() {
        document.querySelectorAll('#assignSubjectsTableBody tr[data-row-index]').forEach((row) => {
            row.classList.remove('table-danger');
            const errorCell = row.querySelector('.assign-row-error');
            if (errorCell) {
                errorCell.innerHTML = '';
            }
        });
    }

    function applyBulkInlineErrors(errors = {}) {
        resetSubjectRowErrors();

        Object.entries(errors).forEach(([index, errorBag]) => {
            const row = document.querySelector(`#assignSubjectsTableBody tr[data-row-index="${index}"]`);
            if (!row) return;
            row.classList.add('table-danger');
            const errorCell = row.querySelector('.assign-row-error');
            if (!errorCell) return;

            const messages = Object.values(errorBag || {})
                .flatMap((value) => (Array.isArray(value) ? value : [value]))
                .filter(Boolean)
                .map((value) => `<div class="small text-danger">${value}</div>`)
                .join('');

            errorCell.innerHTML = messages;
        });
    }

    function updateBulkLoadSummary() {
        const selectedRows = getSelectedSubjectRows();

        const selectedLecture = selectedRows.reduce((sum, row) => sum + row.lecture_hours, 0);
        const selectedLab = selectedRows.reduce((sum, row) => sum + row.lab_hours, 0);

        const currentLecture = Number(loadSummary.current_lecture_hours || 0);
        const currentLab = Number(loadSummary.current_lab_hours || 0);
        const lectureLimit = loadSummary.max_lecture_hours === null ? null : Number(loadSummary.max_lecture_hours);
        const labLimit = loadSummary.max_lab_hours === null ? null : Number(loadSummary.max_lab_hours);

        const projectedLecture = currentLecture + selectedLecture;
        const projectedLab = currentLab + selectedLab;

        const remainingLecture = lectureLimit === null ? null : lectureLimit - projectedLecture;
        const remainingLab = labLimit === null ? null : labLimit - projectedLab;

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        setText('summaryCurrentLecture', currentLecture);
        setText('summaryCurrentLab', currentLab);
        setText('summaryContractType', toTitleCase(loadSummary.contract_type));
        setText('summaryLectureLimit', lectureLimit === null ? 'N/A' : lectureLimit);
        setText('summaryLabLimit', labLimit === null ? 'N/A' : labLimit);
        setText('summarySelectedLecture', selectedLecture);
        setText('summarySelectedLab', selectedLab);
        setText('summaryProjectedLecture', projectedLecture);
        setText('summaryProjectedLab', projectedLab);
        setText('summaryRemainingLecture', remainingLecture === null ? 'N/A' : remainingLecture);
        setText('summaryRemainingLab', remainingLab === null ? 'N/A' : remainingLab);

        const overLimit = (lectureLimit !== null && projectedLecture > lectureLimit)
            || (labLimit !== null && projectedLab > labLimit);

        const warningEl = document.getElementById('summaryLimitWarning');
        if (warningEl) {
            warningEl.classList.toggle('d-none', !overLimit);
        }

        ['summaryProjectedLecture', 'summaryProjectedLab', 'summaryRemainingLecture', 'summaryRemainingLab'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('text-danger');
        });

        if (overLimit) {
            ['summaryProjectedLecture', 'summaryProjectedLab', 'summaryRemainingLecture', 'summaryRemainingLab'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.classList.add('text-danger');
            });
        }

        if (assignSubmitBtn) {
            assignSubmitBtn.disabled = selectedRows.length === 0;
        }
    }

    const hasCompleteOneByOneContext = () => {
        if (!isOneByOneAssignMode) return false;

        return [assignFaculty, assignProgram, assignAcademicYear, assignSemester, assignYearLevel, assignBlockSection, assignSubject]
            .every((field) => field && String(field.value || '').trim() !== '');
    };

    function updateSelectedSubjectPreview() {
        if (!isOneByOneAssignMode || !assignSubject) return;

        const selectedOption = assignSubject.options[assignSubject.selectedIndex];
        const subjectCode = selectedOption?.dataset?.subjectCode || '-';
        const subjectName = selectedOption?.dataset?.subjectName || 'Select a subject to view details';
        const lectureHours = parseInt(selectedOption?.dataset?.lectureHours || '0', 10) || 0;
        const labHours = parseInt(selectedOption?.dataset?.labHours || '0', 10) || 0;

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        setText('assignSubjectCode', subjectCode);
        setText('assignSubjectName', subjectName);
        setText('assignSubjectLecture', lectureHours);
        setText('assignSubjectLab', labHours);

        const lectureInput = document.getElementById('assignLectureHours');
        const labInput = document.getElementById('assignLabHours');
        if (lectureInput) lectureInput.value = String(lectureHours);
        if (labInput) labInput.value = String(labHours);
    }

    function renderAvailabilityRows(rows = []) {
        const body = document.getElementById('assignAvailabilityTableBody');
        if (!body) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-muted">No faculty availability configured.</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${row.day || '-'}</td>
                <td>${row.start_time || '-'}</td>
                <td>${row.end_time || '-'}</td>
            </tr>
        `).join('');
    }

    function updateOneByOneSummaryPanel(payload = null) {
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        const warningEl = document.getElementById('assignLimitWarning');
        const warningTextEl = document.getElementById('assignLimitWarningText');
        const availabilityWarningEl = document.getElementById('assignAvailabilityWarning');
        const availabilityWarningTextEl = document.getElementById('assignAvailabilityWarningText');
        const generalInfoEl = document.getElementById('assignGeneralInfo');
        const submitButton = assignForm?.querySelector('button[type="submit"]');

        if (!payload) {
            setText('assignCurrentLecture', '0 / 0');
            setText('assignCurrentLab', '0 / 0');
            setText('assignRemainingLecture', '0');
            setText('assignRemainingLab', '0');
            setText('assignMaxHoursPerDay', '0');
            renderAvailabilityRows([]);

            if (warningEl) warningEl.classList.add('d-none');
            if (availabilityWarningEl) availabilityWarningEl.classList.add('d-none');
            if (generalInfoEl) generalInfoEl.textContent = 'Select all required fields to view real-time workload validation.';
            if (submitButton) submitButton.disabled = true;
            return;
        }

        const summary = payload.load_summary || {};
        const currentLecture = Number(summary.current_lecture_hours || 0);
        const currentLab = Number(summary.current_lab_hours || 0);
        const maxLecture = summary.max_lecture_hours;
        const maxLab = summary.max_lab_hours;

        setText('assignCurrentLecture', `${currentLecture} / ${maxLecture ?? 'N/A'}`);
        setText('assignCurrentLab', `${currentLab} / ${maxLab ?? 'N/A'}`);
        setText('assignRemainingLecture', summary.remaining_lecture_hours ?? 'N/A');
        setText('assignRemainingLab', summary.remaining_lab_hours ?? 'N/A');
        setText('assignMaxHoursPerDay', summary.max_hours_per_day ?? 'N/A');
        renderAvailabilityRows(payload.availability || []);

        const warningMessages = [];
        const generalWarnings = payload?.warnings?.general || [];
        const dailyWarnings = payload?.warnings?.daily_hours || [];

        if (Array.isArray(generalWarnings) && generalWarnings.length > 0) {
            warningMessages.push(generalWarnings[0]?.message || 'Workload limit exceeded.');
        }
        if (Array.isArray(dailyWarnings) && dailyWarnings.length > 0) {
            warningMessages.push(dailyWarnings[0]?.message || 'Maximum teaching hours per day would be exceeded.');
        }

        if (warningMessages.length > 0) {
            if (warningTextEl) warningTextEl.textContent = warningMessages[0];
            if (warningEl) warningEl.classList.remove('d-none');
        } else if (warningEl) {
            warningEl.classList.add('d-none');
        }

        const availabilityWarnings = payload?.warnings?.availability || [];
        if (Array.isArray(availabilityWarnings) && availabilityWarnings.length > 0) {
            if (availabilityWarningTextEl) {
                availabilityWarningTextEl.textContent = availabilityWarnings[0]?.message || 'Selected subject schedule conflicts with faculty availability.';
            }
            if (availabilityWarningEl) availabilityWarningEl.classList.remove('d-none');
        } else if (availabilityWarningEl) {
            availabilityWarningEl.classList.add('d-none');
        }

        if (generalInfoEl) {
            generalInfoEl.textContent = payload.can_assign
                ? 'Assignment is valid based on current faculty workload configuration.'
                : 'Assignment cannot proceed due to workload configuration limits.';
        }

        if (submitButton) {
            submitButton.disabled = !payload.can_assign;
        }
    }

    function loadOneByOneAssignmentContext() {
        if (!isOneByOneAssignMode) return;

        updateSelectedSubjectPreview();

        if (!hasCompleteOneByOneContext()) {
            updateOneByOneSummaryPanel(null);
            return;
        }

        const params = new URLSearchParams({
            faculty_id: assignFaculty.value,
            program_id: assignProgram.value,
            academic_year_id: assignAcademicYear.value,
            semester: assignSemester.value,
            year_level: assignYearLevel.value,
            block_section: assignBlockSection.value.trim(),
            subject_id: assignSubject.value,
        }).toString();

        fetch(`${baseUrl}/api/assignment-context?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((data) => {
                        throw new Error(data.message || 'Failed to load assignment context.');
                    });
                }
                return response.json();
            })
            .then((payload) => {
                const data = payload?.data || null;
                if (data?.subject) {
                    const lectureInput = document.getElementById('assignLectureHours');
                    const labInput = document.getElementById('assignLabHours');
                    if (lectureInput) lectureInput.value = String(data.subject.lecture_hours || 0);
                    if (labInput) labInput.value = String(data.subject.lab_hours || 0);
                }
                updateOneByOneSummaryPanel(data);
            })
            .catch((error) => {
                updateOneByOneSummaryPanel(null);
                showAssignMessage('error', error.message || 'Failed to load assignment context.');
            });
    }

    function syncRowBlocksWithGlobalBlock() {
        const block = assignBlockSection?.value?.trim() || '';
        document.querySelectorAll('#assignSubjectsTableBody .assign-row-block').forEach((input) => {
            if (input.disabled) return;
            input.value = block;
        });
    }

    function loadAssignableSubjects() {
        resetSubjectRowErrors();

        if (!hasCompleteAssignmentContext()) {
            assignableSubjects = [];
            loadSummary = {
                current_lecture_hours: 0,
                current_lab_hours: 0,
                contract_type: 'unspecified',
                max_lecture_hours: null,
                max_lab_hours: null,
            };
            renderAssignableSubjectsTable([]);
            return;
        }

        const context = getAssignmentContext();
        const params = new URLSearchParams(context).toString();

        fetch(`${baseUrl}/api/assignable-subjects?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((data) => {
                        throw new Error(data.message || 'Failed to load subjects.');
                    });
                }
                return response.json();
            })
            .then((payload) => {
                assignableSubjects = payload.subjects || [];
                loadSummary = payload.load_summary || loadSummary;
                renderAssignableSubjectsTable(assignableSubjects);
                updateBulkLoadSummary();
            })
            .catch((error) => {
                assignableSubjects = [];
                renderAssignableSubjectsTable([]);
                showAssignMessage('error', error.message || 'Failed to load assignable subjects.');
            });
    }

    if (isBulkAssignMode) {
        [assignFaculty, assignProgram, assignAcademicYear, assignSemester, assignYearLevel].forEach((field) => {
            if (!field) return;
            field.addEventListener('change', loadAssignableSubjects);
        });

        if (assignBlockSection) {
            assignBlockSection.addEventListener('input', () => {
                syncRowBlocksWithGlobalBlock();
                updateBulkLoadSummary();
                if (hasCompleteAssignmentContext()) {
                    loadAssignableSubjects();
                }
            });
        }

        if (assignSelectAllSubjects) {
            assignSelectAllSubjects.addEventListener('change', function () {
                const shouldCheck = this.checked;
                document.querySelectorAll('#assignSubjectsTableBody .assign-subject-checkbox:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = shouldCheck;
                });
                updateBulkLoadSummary();
            });
        }
    }

    if (isOneByOneAssignMode) {
        [assignFaculty, assignProgram, assignAcademicYear, assignSemester, assignYearLevel].forEach((field) => {
            if (!field) return;
            field.addEventListener('change', loadOneByOneAssignmentContext);
        });

        if (assignBlockSection) {
            assignBlockSection.addEventListener('input', loadOneByOneAssignmentContext);
        }

        if (assignSubject) {
            assignSubject.addEventListener('change', loadOneByOneAssignmentContext);
        }

        updateSelectedSubjectPreview();
        updateOneByOneSummaryPanel(null);
    }

    const submitAssign = (forceAssign = false) => {
        clearAssignMessage();
        resetSubjectRowErrors();

        if (!assignForm.checkValidity()) {
            assignForm.classList.add('was-validated');
            return;
        }

        const selectedSubjects = isBulkAssignMode ? getSelectedSubjectRows() : [];
        if (isBulkAssignMode && selectedSubjects.length === 0) {
            showAssignMessage('error', 'Select at least one subject before submitting.');
            return;
        }

        if (isOneByOneAssignMode && !hasCompleteOneByOneContext()) {
            showAssignMessage('error', 'Please complete all required fields before submitting.');
            return;
        }

        const submitBtn = assignForm.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';

        const formData = new FormData(assignForm);
        if (isBulkAssignMode) {
            formData.delete('subject_id');
            formData.delete('lecture_hours');
            formData.delete('lab_hours');

            selectedSubjects.forEach((subject, index) => {
                formData.append(`subjects[${index}][subject_id]`, subject.subject_id.toString());
                formData.append(`subjects[${index}][block]`, subject.block);
                formData.append(`subjects[${index}][lecture_hours]`, subject.lecture_hours.toString());
                formData.append(`subjects[${index}][lab_hours]`, subject.lab_hours.toString());
            });
        }

        if (forceAssign) {
            formData.set('force_assign', '1');
        }

        fetch(`${baseUrl}/assign`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then((response) => {
                if (response.status === 409) {
                    return response.json().then((data) => {
                        throw { overload: true, data };
                    });
                }
                if (!response.ok) {
                    return response.json().then((data) => {
                        const error = new Error(data.message || 'Failed to assign faculty load');
                        error.payload = data;
                        throw error;
                    });
                }
                return response.json();
            })
            .then((data) => {
                assignForm.reset();
                assignForm.classList.remove('was-validated');
                if (isBulkAssignMode) {
                    assignableSubjects = [];
                    renderAssignableSubjectsTable([]);
                    updateBulkLoadSummary();
                }
                if (isOneByOneAssignMode) {
                    updateSelectedSubjectPreview();
                    updateOneByOneSummaryPanel(null);
                }
                showAssignMessage('success', data?.message || 'Faculty load assigned successfully.');
                setTimeout(() => {
                    assignModal.hide();
                }, 1500);
            })
            .catch((error) => {
                if (error.overload) {
                    pendingForceAction = () => submitAssign(true);
                    showOverloadModal(error.data);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                    return;
                }
                if (error?.payload?.errors) {
                    applyBulkInlineErrors(error.payload.errors);
                }
                console.error('Error assigning faculty load:', error);
                showAssignMessage('error', error.message || 'Failed to assign faculty load.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
    };

    // Assign Faculty Load Form
    assignForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (isOneByOneAssignMode) {
            submitAssign(false);
            return;
        }

        const selectedSubjects = getSelectedSubjectRows();
        if (selectedSubjects.length === 0) {
            showAssignMessage('error', 'Select at least one subject before submitting.');
            return;
        }

        const confirmCountEl = document.getElementById('assignConfirmCount');
        if (confirmCountEl) {
            confirmCountEl.textContent = selectedSubjects.length.toString();
        }

        if (assignConfirmationModal) {
            assignConfirmationModal.show();
        } else {
            submitAssign(false);
        }
    });

    if (confirmAssignSubjectsBtn) {
        confirmAssignSubjectsBtn.addEventListener('click', () => {
            if (assignConfirmationModal) {
                assignConfirmationModal.hide();
            }
            submitAssign(false);
        });
    }

    const submitEdit = (forceAssign = false) => {
        if (!editForm.checkValidity()) {
            editForm.classList.add('was-validated');
            return;
        }

        const submitBtn = editForm.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        const facultyLoadId = document.getElementById('editFacultyLoadId').value;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

        const formData = new FormData(editForm);
        if (forceAssign) {
            formData.set('force_assign', '1');
        }

        fetch(`${baseUrl}/update-constraints`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then((response) => {
                if (response.status === 409) {
                    return response.json().then((data) => {
                        throw { overload: true, data };
                    });
                }
                if (!response.ok) {
                    return response.json().then((data) => {
                        throw new Error(data.message || 'Failed to update faculty load');
                    });
                }
                return response.json();
            })
            .then(() => {
                editModal.hide();
                editForm.reset();
                editForm.classList.remove('was-validated');
                showAlert('success', 'Faculty load updated successfully.');
                setTimeout(() => location.reload(), 1500);
            })
            .catch((error) => {
                if (error.overload) {
                    pendingForceAction = () => submitEdit(true);
                    showOverloadModal(error.data);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                    return;
                }
                console.error('Error updating faculty load:', error);
                showAlert('error', error.message || 'Failed to update faculty load.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
    };

    // Edit Faculty Load Form
    editForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitEdit(false);
    });

    // =========================================
    // UTILITY FUNCTIONS
    // =========================================

    /**
     * Calculate total hours based on lecture and lab hours.
     */
    function calculateTeachingUnits(lectureHours, labHours) {
        return (lectureHours + labHours).toFixed(0);
    }

    /**
     * Validate lab hours divisibility by 3
     */
    function validateLabHours(labHours) {
        if (labHours > 0 && labHours % 3 !== 0) {
            return {
                valid: false,
                message: 'Laboratory hours must be divisible by 3'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Update computed units display
     */
    function updateComputedUnits(lectureInput, labInput, displayElement) {
        const lectureHours = parseInt(lectureInput.value) || 0;
        const labHours = parseInt(labInput.value) || 0;
        const units = calculateTeachingUnits(lectureHours, labHours);
        displayElement.textContent = units;
    }

    function showOverloadModal(payload) {
        const details = payload?.validation_details || {};
        document.getElementById('overloadMessage').textContent = payload?.message || 'This assignment exceeds the load limit.';
        document.getElementById('overloadCurrentLecture').textContent = details.current?.lecture_hours ?? 0;
        document.getElementById('overloadCurrentLab').textContent = details.current?.lab_hours ?? 0;
        document.getElementById('overloadMaxLecture').textContent = details.limits?.max_lecture_hours ?? '—';
        document.getElementById('overloadMaxLab').textContent = details.limits?.max_lab_hours ?? '—';

        const newLecture = details.new?.lecture_hours ?? 0;
        const newLab = details.new?.lab_hours ?? 0;
        const maxLecture = details.limits?.max_lecture_hours ?? null;
        const maxLab = details.limits?.max_lab_hours ?? null;
        const lectureExcess = maxLecture === null ? 0 : Math.max(0, newLecture - maxLecture);
        const labExcess = maxLab === null ? 0 : Math.max(0, newLab - maxLab);
        const totalExcess = lectureExcess + labExcess;
        document.getElementById('overloadExcess').textContent = totalExcess;

        overloadModal.show();
    }

    // =========================================
    // REAL-TIME UNIT CALCULATION
    // =========================================

    // Edit Modal - Unit Calculation
    const editLectureHours = document.getElementById('editLectureHours');
    const editLabHours = document.getElementById('editLabHours');
    const editComputedUnits = document.getElementById('editComputedUnits');

    if (editLectureHours && editLabHours && editComputedUnits) {
        editLectureHours.addEventListener('input', function() {
            updateComputedUnits(editLectureHours, editLabHours, editComputedUnits);
        });

        editLabHours.addEventListener('input', function() {
            updateComputedUnits(editLectureHours, editLabHours, editComputedUnits);

            // Validate lab hours divisibility
            const validation = validateLabHours(parseInt(this.value) || 0);
            if (!validation.valid) {
                this.setCustomValidity(validation.message);
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = validation.message;
                }
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }

    function showAlert(type, message) {
        const container = document.getElementById('globalToastContainer');
        if (!container) {
            return;
        }

        const toastEl = document.createElement('div');
        const toastClass = type === 'error' ? 'text-bg-danger' : 'text-bg-success';
        const iconClass = type === 'error' ? 'circle-exclamation' : 'circle-check';
        toastEl.className = `toast align-items-center ${toastClass} border-0 shadow-sm`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fa-solid fa-${iconClass} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function showAssignMessage(type, message) {
        const container = document.getElementById('assignFacultyLoadMessage');
        if (!container) {
            showAlert(type, message);
            return;
        }

        const alertClass = type === 'error' ? 'danger' : 'success';
        const iconClass = type === 'error' ? 'circle-exclamation' : 'circle-check';
        const label = type === 'error' ? 'Error!' : 'Success!';

        container.classList.remove('d-none');
        container.innerHTML = `
            <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-${iconClass} me-2"></i>
                <strong>${label}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    function clearAssignMessage() {
        const container = document.getElementById('assignFacultyLoadMessage');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        container.classList.add('d-none');
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    // =========================================
    // TOOLTIP INITIALIZATION
    // =========================================

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });

    // =========================================
    // INITIAL CLEANUP
    // =========================================

    // Remove validation classes on modal hide
    document.getElementById('assignFacultyLoadModal').addEventListener('hidden.bs.modal', function () {
        assignForm.classList.remove('was-validated');
        assignForm.reset();
        clearAssignMessage();
        resetSubjectRowErrors();
        assignableSubjects = [];
        loadSummary = {
            current_lecture_hours: 0,
            current_lab_hours: 0,
            contract_type: 'unspecified',
            max_lecture_hours: null,
            max_lab_hours: null,
        };
        renderAssignableSubjectsTable([]);
        updateBulkLoadSummary();
        if (assignSelectAllSubjects) {
            assignSelectAllSubjects.checked = false;
        }

        if (isOneByOneAssignMode) {
            updateSelectedSubjectPreview();
            updateOneByOneSummaryPanel(null);
        }
    });

    document.getElementById('editFacultyLoadModal').addEventListener('hidden.bs.modal', function () {
        editForm.classList.remove('was-validated');
        editForm.reset();
    });
});
        
                // Load workload configuration when faculty or program changes
                if (assignFaculty) {
                    assignFaculty.addEventListener('change', loadFacultyWorkloadConfiguration);
                }
                if (assignProgram) {
                    assignProgram.addEventListener('change', loadFacultyWorkloadConfiguration);
                }
        
            // Load workload configuration when faculty or program changes
            if (assignFaculty) {
                assignFaculty.addEventListener('change', loadFacultyWorkloadConfiguration);
            }
            if (assignProgram) {
                assignProgram.addEventListener('change', loadFacultyWorkloadConfiguration);
            }
    function loadFacultyWorkloadConfiguration() {
        // Only fetch when both faculty and program are selected
        if (!assignFaculty?.value || !assignProgram?.value) {
            renderAvailabilityRows([]);
            return;
        }

        const params = new URLSearchParams({
            faculty_id: assignFaculty.value,
            program_id: assignProgram.value,
        }).toString();

        fetch(`${baseUrl}/api/workload-configuration?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((data) => {
                        const errorMsg = data.message || 'Failed to load workload configuration.';
                        throw new Error(errorMsg);
                    });
                }
                return response.json();
            })
            .then((payload) => {
                if (payload.success && payload.data) {
                    const config = payload.data;
                    
                    // Render the teaching schedule availability
                    const availabilityRows = (config.teaching_schedule || [])
                        .filter(s => s.is_available)
                        .map(s => ({
                            day: s.day,
                            start_time: s.start_time,
                            end_time: s.end_time,
                        }));
                    
                    renderAvailabilityRows(availabilityRows);
                    
                    // Update max hours per day if displayed
                    const maxHoursElement = document.getElementById('assignMaxHoursPerDay');
                    if (maxHoursElement) {
                        maxHoursElement.textContent = config.max_hours_per_day || 'N/A';
                    }
                    
                    console.log('Faculty workload configuration loaded:', config);
                } else {
                    renderAvailabilityRows([]);
                    console.warn('No workload configuration found for the selected faculty and program.');
                }
            })
            .catch((error) => {
                renderAvailabilityRows([]);
                console.warn('Warning: Could not load faculty workload configuration.', error.message);
            });
    }

