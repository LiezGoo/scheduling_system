<!-- Edit User Profile Modal -->
<div class="modal fade" id="editUserProfileModal" tabindex="-1" aria-labelledby="editUserProfileModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="editUserProfileModalLabel">
                    <i class="fa-solid fa-user-pen me-2"></i>Edit Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="editUserProfileForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" id="profileUserId" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profileFirstName" class="form-label">First Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profileFirstName" name="first_name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="profileLastName" class="form-label">Last Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profileLastName" name="last_name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="profileEmail" class="form-label">Email Address <span
                                class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="profileEmail" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <hr class="my-4">
                    <p class="text-muted small mb-3">
                        <i class="fa-solid fa-lock me-1"></i>Leave password fields blank to keep current password
                    </p>
                    <div class="mb-3">
                        <label for="profilePassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="profilePassword" name="password">
                        <small class="form-text text-muted">Minimum 8 characters</small>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="profilePasswordConfirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="profilePasswordConfirmation"
                            name="password_confirmation">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon">
                        <i class="fa-solid fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
