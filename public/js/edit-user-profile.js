/**
 * User Profile Edit Modal JavaScript
 * Handles AJAX operations for user profile editing
 */

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const editUserProfileModal = new bootstrap.Modal(document.getElementById('editUserProfileModal'));
    const editUserProfileForm = document.getElementById('editUserProfileForm');

    // Open Edit Profile Modal
    const editProfileButtons = document.querySelectorAll('[data-edit-profile-btn]');
    editProfileButtons.forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.getAttribute('data-user-id') || null;
            loadProfileData(userId);
        });
    });

    // Load Profile Data
    function loadProfileData(userId) {
        // If no userId provided, use current authenticated user from meta tag
        const currentUserId = userId || document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const endpoint = `/admin/users/${currentUserId}`;

        fetch(endpoint, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('profileUserId').value = user.id;
                    document.getElementById('profileFirstName').value = user.first_name;
                    document.getElementById('profileLastName').value = user.last_name;
                    document.getElementById('profileEmail').value = user.email;

                    // Clear password fields
                    document.getElementById('profilePassword').value = '';
                    document.getElementById('profilePasswordConfirmation').value = '';

                    clearValidationErrors();
                    editUserProfileModal.show();
                } else {
                    showToast('error', data.message || 'Failed to load profile data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An unexpected error occurred');
            });
    }

    // Submit Edit Profile Form
    if (editUserProfileForm) {
        editUserProfileForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearValidationErrors();

            const userId = document.getElementById('profileUserId').value;
            const formData = new FormData(editUserProfileForm);
            const submitBtn = editUserProfileForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...';

            const endpoint = userId ? `/admin/users/${userId}` : '/user/profile';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message || 'Profile updated successfully');
                        editUserProfileModal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        if (data.errors) {
                            displayValidationErrors(data.errors);
                        } else {
                            showToast('error', data.message || 'Failed to update profile');
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

    // Helper: Display Validation Errors
    function displayValidationErrors(errors) {
        const fieldMap = {
            'first_name': 'profileFirstName',
            'last_name': 'profileLastName',
            'email': 'profileEmail',
            'password': 'profilePassword',
            'password_confirmation': 'profilePasswordConfirmation',
        };

        Object.keys(errors).forEach(fieldName => {
            const inputId = fieldMap[fieldName] || `profile${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`;
            const inputElement = document.getElementById(inputId);
            if (inputElement) {
                inputElement.classList.add('is-invalid');
                const feedbackElement = inputElement.nextElementSibling;
                if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
                    feedbackElement.textContent = errors[fieldName][0];
                }
            }
        });
    }

    // Helper: Clear Validation Errors
    function clearValidationErrors() {
        const form = document.getElementById('editUserProfileForm');
        if (form) {
            form.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('is-invalid');
                const feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            });
        }
    }

    // Helper: Show Toast Notification
    function showToast(type, message) {
        // Check if toast container exists, if not create it
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
        const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

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
        const toast = new bootstrap.Toast(toastElement);
        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Reset modal on close
    document.getElementById('editUserProfileModal').addEventListener('hidden.bs.modal', function () {
        editUserProfileForm.reset();
        clearValidationErrors();
    });
});
