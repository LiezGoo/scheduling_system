/**
 * User Management JavaScript
 * Handles AJAX operations for user CRUD
 */

document.addEventListener('DOMContentLoaded', function () {
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Initialize Bootstrap modals
    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    const toggleStatusModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));

    // Debounce helper for text-driven filters
    const debounce = (fn, delay = 400) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn(...args), delay);
        };
    };

    // Auto-filter wiring
    const filterForm = document.getElementById('filterForm');
    const usersTableBody = document.getElementById('usersTableBody');
    const usersPagination = document.getElementById('usersPagination');
    const usersSummary = document.getElementById('usersSummary');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const filtersSpinner = document.getElementById('filtersSpinner');

    const toggleFiltersSpinner = show => {
        if (!filtersSpinner) return;
        filtersSpinner.classList.toggle('d-none', !show);
    };

    const applyFilters = (options = {}) => {
        if (!filterForm) return;

        const formData = new FormData(filterForm);
        const params = new URLSearchParams();

        formData.forEach((value, key) => {
            if (value && value.toString().trim() !== '') {
                params.append(key, value.toString().trim());
            }
        });

        if (options.page) {
            params.set('page', options.page);
        }

        const listUrl = filterForm.dataset.listUrl || filterForm.action;
        const requestUrl = params.toString() ? `${listUrl}?${params.toString()}` : listUrl;

        toggleFiltersSpinner(true);

        fetch(requestUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Filtering failed');
                }

                if (usersTableBody) usersTableBody.innerHTML = data.rows;
                if (usersPagination) usersPagination.innerHTML = data.pagination;
                if (usersSummary) usersSummary.innerHTML = data.summary;
            })
            .catch(error => {
                console.error('Filter error:', error);
            })
            .finally(() => {
                toggleFiltersSpinner(false);
            });
    };

    const debouncedApplyFilters = debounce(() => applyFilters(), 400);

    if (filterForm) {
        filterForm.addEventListener('submit', e => e.preventDefault());

        filterForm.querySelectorAll('input[type="text"], input[type="search"], select').forEach(element => {
            if (element.tagName === 'SELECT') {
                element.addEventListener('change', () => applyFilters());
            } else {
                element.addEventListener('input', () => debouncedApplyFilters());
            }
        });

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                filterForm.reset();
                applyFilters();
            });
        }
    }

    document.addEventListener('click', e => {
        const paginationLink = e.target.closest('#usersPagination a');
        if (!paginationLink) return;

        e.preventDefault();
        const url = new URL(paginationLink.href);
        const page = url.searchParams.get('page') || 1;
        applyFilters({ page });
    });

    // Per-page selector
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', 1); // Reset to first page
            window.location.href = url.toString();
        });
    }

    // Add User Form Submission
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearValidationErrors(addUserForm);

            const formData = new FormData(addUserForm);
            const submitBtn = addUserForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creating...';

            fetch('/admin/users', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message);
                        addUserModal.hide();
                        addUserForm.reset();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        if (data.errors) {
                            displayValidationErrors(addUserForm, data.errors);
                        } else {
                            showToast('error', data.message || 'Failed to create user');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An unexpected error occurred');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });
    }

    // View User Button Click (using event delegation)
    document.addEventListener('click', function (e) {
        if (e.target.closest('.view-user-btn')) {
            const button = e.target.closest('.view-user-btn');
            const userId = button.getAttribute('data-user-id');
            loadUserForView(userId);
        }
    });

    // Load User Data for Viewing
    function loadUserForView(userId) {
        fetch(`/admin/users/${userId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;

                    // Update avatar with first letter of first name
                    const avatarElement = document.getElementById('viewUserAvatar');
                    if (avatarElement && user.first_name) {
                        avatarElement.textContent = user.first_name.charAt(0).toUpperCase();
                    }

                    // Update user details
                    document.getElementById('viewUserFullName').textContent = user.full_name || `${user.first_name} ${user.last_name}`;
                    document.getElementById('viewUserFirstName').textContent = user.first_name || 'N/A';
                    document.getElementById('viewUserLastName').textContent = user.last_name || 'N/A';
                    document.getElementById('viewUserEmail').textContent = user.email || 'N/A';

                    // Update role badge
                    const roleElement = document.getElementById('viewUserRole');
                    if (roleElement) {
                        const roleLabel = user.role_label || user.role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        roleElement.textContent = roleLabel;
                    }

                    // Update status badge
                    const statusBadge = document.getElementById('viewUserStatusBadge');
                    if (statusBadge) {
                        statusBadge.textContent = user.status.charAt(0).toUpperCase() + user.status.slice(1);
                        statusBadge.className = 'badge status-badge ' + (user.status === 'active' ? 'bg-success' : 'bg-danger');
                    }

                    // Store user ID for edit button
                    document.getElementById('viewUserEditBtn').setAttribute('data-user-id', user.id);

                    viewUserModal.show();
                } else {
                    showToast('error', 'Failed to load user data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An unexpected error occurred');
            });
    }

    // Edit button in View Modal
    document.getElementById('viewUserEditBtn').addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        viewUserModal.hide();
        // Wait for view modal to close before opening edit modal
        setTimeout(() => {
            loadUserData(userId);
        }, 300);
    });

    // Edit User Button Click (using event delegation)
    document.addEventListener('click', function (e) {
        if (e.target.closest('.edit-user-btn')) {
            const button = e.target.closest('.edit-user-btn');
            const userId = button.getAttribute('data-user-id');
            loadUserData(userId);
        }
    });

    // Load User Data for Editing
    function loadUserData(userId) {
        fetch(`/admin/users/${userId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('editUserId').value = user.id;
                    document.getElementById('editFirstName').value = user.first_name;
                    document.getElementById('editLastName').value = user.last_name;
                    document.getElementById('editEmail').value = user.email;
                    document.getElementById('editRole').value = user.role;
                    document.getElementById('editStatus').value = user.status;

                    // Clear password fields
                    document.getElementById('editPassword').value = '';
                    document.getElementById('editPasswordConfirmation').value = '';

                    clearValidationErrors(document.getElementById('editUserForm'));
                    editUserModal.show();
                } else {
                    showToast('error', 'Failed to load user data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An unexpected error occurred');
            });
    }

    // Edit User Form Submission
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearValidationErrors(editUserForm);

            const userId = document.getElementById('editUserId').value;
            const formData = new FormData(editUserForm);
            const submitBtn = editUserForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...';

            fetch(`/admin/users/${userId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message);
                        editUserModal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        if (data.errors) {
                            displayValidationErrors(editUserForm, data.errors);
                        } else {
                            showToast('error', data.message || 'Failed to update user');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An unexpected error occurred');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });
    }

    // Toggle Status Button Click (using event delegation)
    let userToToggle = null;
    let buttonToToggle = null;
    document.addEventListener('click', function (e) {
        if (e.target.closest('.toggle-status-btn')) {
            const button = e.target.closest('.toggle-status-btn');
            if (button.disabled) return;

            userToToggle = button.getAttribute('data-user-id');
            buttonToToggle = button;
            const currentStatus = button.getAttribute('data-current-status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            // Get user name from the row
            const row = button.closest('tr');
            const userName = row.querySelector('.fw-semibold').textContent.trim();

            // Update modal content
            document.getElementById('toggleActionText').textContent = newStatus === 'active' ? 'activate' : 'deactivate';
            document.getElementById('toggleUserName').textContent = userName;
            document.getElementById('toggleStatusMessage').textContent =
                newStatus === 'active'
                    ? 'This user will be able to access the system again.'
                    : 'This user will no longer be able to access the system.';

            toggleStatusModal.show();
        }
    });

    // Confirm Toggle Status Button
    const confirmToggleStatusBtn = document.getElementById('confirmToggleStatusBtn');
    if (confirmToggleStatusBtn) {
        confirmToggleStatusBtn.addEventListener('click', function () {
            if (!userToToggle || !buttonToToggle) return;

            toggleUserStatus(userToToggle, buttonToToggle);
            toggleStatusModal.hide();
        });
    }

    // Toggle User Status
    function toggleUserStatus(userId, buttonElement) {
        const originalBtnContent = buttonElement.innerHTML;
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch(`/admin/users/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-HTTP-Method-Override': 'PATCH'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('error', data.message || 'Failed to toggle status');
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalBtnContent;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An unexpected error occurred');
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalBtnContent;
            });
    }

    // Delete User Button Click (using event delegation)
    let userToDelete = null;
    document.addEventListener('click', function (e) {
        if (e.target.closest('.delete-user-btn')) {
            const button = e.target.closest('.delete-user-btn');
            if (button.disabled) return;

            userToDelete = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');

            document.getElementById('deleteUserName').textContent = userName;
            deleteUserModal.show();
        }
    });

    // Confirm Delete Button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (!userToDelete) return;

            const originalBtnText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Deleting...';

            fetch(`/admin/users/${userToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message);
                        deleteUserModal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast('error', data.message || 'Failed to delete user');
                        this.disabled = false;
                        this.innerHTML = originalBtnText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An unexpected error occurred');
                    this.disabled = false;
                    this.innerHTML = originalBtnText;
                });
        });
    }

    // Helper: Display Validation Errors
    function displayValidationErrors(form, errors) {
        Object.keys(errors).forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = errors[fieldName][0];
                }
            }
        });
    }

    // Helper: Clear Validation Errors
    function clearValidationErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.textContent = '';
        });
    }

    // Helper: Format Date Time
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';

        try {
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        } catch (error) {
            return dateString;
        }
    }

    // Helper: Show Toast Notification
    function showToast(type, message) {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fa-solid ${iconClass} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Reset modals on close
    document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
        addUserForm.reset();
        clearValidationErrors(addUserForm);
    });

    document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
        editUserForm.reset();
        clearValidationErrors(editUserForm);
    });
});
