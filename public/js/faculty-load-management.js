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

    const submitAssign = (forceAssign = false) => {
        clearAssignMessage();
        if (!assignForm.checkValidity()) {
            assignForm.classList.add('was-validated');
            return;
        }

        const submitBtn = assignForm.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';

        const formData = new FormData(assignForm);
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
                        throw new Error(data.message || 'Failed to assign faculty load');
                    });
                }
                return response.json();
            })
            .then(() => {
                assignForm.reset();
                assignForm.classList.remove('was-validated');
                showAssignMessage('success', 'Faculty load assigned successfully.');
                setTimeout(() => {
                    assignModal.hide();
                    location.reload();
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
                console.error('Error assigning faculty load:', error);
                showAssignMessage('error', error.message || 'Failed to assign faculty load.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
    };

    // Assign Faculty Load Form
    assignForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitAssign(false);
    });

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

    // Assign Modal - Unit Calculation
    const assignLectureHours = document.getElementById('assignLectureHours');
    const assignLabHours = document.getElementById('assignLabHours');
    const assignComputedUnits = document.getElementById('assignComputedUnits');

    if (assignLectureHours && assignLabHours && assignComputedUnits) {
        assignLectureHours.addEventListener('input', function() {
            updateComputedUnits(assignLectureHours, assignLabHours, assignComputedUnits);
        });

        assignLabHours.addEventListener('input', function() {
            updateComputedUnits(assignLectureHours, assignLabHours, assignComputedUnits);

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
    });

    document.getElementById('editFacultyLoadModal').addEventListener('hidden.bs.modal', function () {
        editForm.classList.remove('was-validated');
        editForm.reset();
    });
});
