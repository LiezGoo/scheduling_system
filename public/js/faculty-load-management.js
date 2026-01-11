/**
 * Faculty Load Management - UI Interactions
 * Handles modals, forms, filters, and AJAX operations
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================
    // MODAL & FORM MANAGEMENT
    // =========================================

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
        fetch(`/admin/faculty-load/${id}/details`, {
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
        document.getElementById('viewSubjectName').textContent = data.subject.subject_name;
        document.getElementById('viewSubjectCode').textContent = data.subject.subject_code;
        document.getElementById('viewProgramName').textContent = data.program.program_name;
        document.getElementById('viewUnits').textContent = data.subject.units;
        document.getElementById('viewMaxSections').textContent = data.max_sections;
        document.getElementById('viewMaxLoadUnits').textContent = data.max_load_units || 'Same as subject units';
        document.getElementById('viewStatus').textContent = 'Active';
        document.getElementById('viewAssignedDate').textContent = formatDate(data.created_at);

        // Set edit button functionality
        document.getElementById('viewEditBtn').onclick = function () {
            viewModal.hide();
            handleEditAction(data.id);
        };
    }

    // Edit Faculty Load
    function handleEditAction(id) {
        fetch(`/admin/faculty-load/${id}/details`, {
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
        document.getElementById('editMaxSections').value = data.max_sections;
        document.getElementById('editMaxLoadUnits').value = data.max_load_units || '';
        document.getElementById('editNotes').value = data.notes || '';
    }

    // Remove Faculty Load
    function handleRemoveAction(id) {
        fetch(`/admin/faculty-load/${id}/details`, {
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

        fetch(`/admin/faculty-load/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
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

    // Assign Faculty Load Form
    assignForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';

        const formData = new FormData(this);

        fetch('/admin/faculty-load/assign', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((data) => {
                        throw new Error(data.message || 'Failed to assign faculty load');
                    });
                }
                return response.json();
            })
            .then((data) => {
                assignModal.hide();
                assignForm.reset();
                assignForm.classList.remove('was-validated');
                showAlert('success', 'Faculty load assigned successfully.');
                setTimeout(() => location.reload(), 1500);
            })
            .catch((error) => {
                console.error('Error assigning faculty load:', error);
                showAlert('error', error.message || 'Failed to assign faculty load.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
    });

    // Edit Faculty Load Form
    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        const facultyLoadId = document.getElementById('editFacultyLoadId').value;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

        const formData = new FormData(this);

        fetch(`/admin/faculty-load/${facultyLoadId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then((data) => {
                        throw new Error(data.message || 'Failed to update faculty load');
                    });
                }
                return response.json();
            })
            .then((data) => {
                editModal.hide();
                editForm.reset();
                editForm.classList.remove('was-validated');
                showAlert('success', 'Faculty load updated successfully.');
                setTimeout(() => location.reload(), 1500);
            })
            .catch((error) => {
                console.error('Error updating faculty load:', error);
                showAlert('error', error.message || 'Failed to update faculty load.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
    });

    // =========================================
    // UTILITY FUNCTIONS
    // =========================================

    function showAlert(type, message) {
        const alertId = `alert-${Date.now()}`;
        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        const container = document.querySelector('.container-fluid');
        const alertElement = document.createElement('div');
        alertElement.innerHTML = alertHTML;
        container.insertBefore(alertElement.firstElementChild, container.firstChild);

        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) alert.remove();
        }, 5000);
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
    });

    document.getElementById('editFacultyLoadModal').addEventListener('hidden.bs.modal', function () {
        editForm.classList.remove('was-validated');
        editForm.reset();
    });
});
