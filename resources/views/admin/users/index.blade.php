@extends('layouts.app')

@section('page-title', 'User & Role Management')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted mb-0"> <i class="fa-solid fa-users-gear me-2"></i>
                    Manage system users, roles, and permissions</p>
            </div>
            <button type="button" class="btn btn-maroon" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-plus me-2"></i>Add New User
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.users.index') }}" id="filterForm"
                    data-list-url="{{ route('admin.users.index') }}" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filterName" class="form-label">Search by Name</label>
                            <input type="text" class="form-control" id="filterName" name="name"
                                placeholder="Enter name..." value="{{ request('name') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filterRole" class="form-label">Filter by Role</label>
                            <select class="form-select" id="filterRole" name="role">
                                <option value="">All Roles</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterStatus" class="form-label">Filter by Status</label>
                            <select class="form-select" id="filterStatus" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters"
                                title="Clear Filters">
                                <i class="fa-solid fa-rotate-left me-1"></i>Clear
                            </button>
                            <div class="spinner-border spinner-border-sm text-maroon d-none" role="status"
                                aria-hidden="true" id="filtersSpinner"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="usersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Organizational Scope</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            @include('admin.users.partials.table-rows')
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($users && $users->count() > 0)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
                        <div class="text-muted small" id="usersSummary">
                            @include('admin.users.partials.summary')
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div id="usersPagination">
                                @include('admin.users.partials.pagination')
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="perPageSelect" class="text-muted small mb-0 text-nowrap">Per page:</label>
                                <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                                    <option value="10" {{ request('per_page', '15') == '10' ? 'selected' : '' }}>10
                                    </option>
                                    <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15
                                    </option>
                                    <option value="25" {{ request('per_page', '15') == '25' ? 'selected' : '' }}>25
                                    </option>
                                    <option value="50" {{ request('per_page', '15') == '50' ? 'selected' : '' }}>50
                                    </option>
                                    <option value="100" {{ request('per_page', '15') == '100' ? 'selected' : '' }}>100
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i class="fa-solid fa-user-plus me-2"></i>Add New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addUserForm" novalidate class="d-flex flex-column flex-grow-1">
                    @csrf
                    <div class="modal-body" style="max-height: calc(90vh - 220px); overflow-y: auto;">
                        <div class="mb-3">
                            <label for="addFirstName" class="form-label">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addFirstName" name="first_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addLastName" class="form-label">Last Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addLastName" name="last_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addEmail" class="form-label">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="addEmail" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addPassword" class="form-label">Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="addPassword" name="password" required>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addPasswordConfirmation" class="form-label">Confirm Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="addPasswordConfirmation"
                                name="password_confirmation" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="addRole" name="role" required>
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Department Assignment (for Department Heads) -->
                        <div class="mb-3" id="addDepartmentField" style="display: none;">
                            <label for="addDepartment" class="form-label">
                                Department Assignment <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="addDepartment" name="department_id">
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Program Assignment (for Program Heads) -->
                        <div class="mb-3" id="addProgramField" style="display: none;">
                            <label for="addProgram" class="form-label">
                                Program Assignment <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="addProgram" name="program_id">
                                <option value="">Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="addStatus" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="addStatus" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content d-flex flex-column" style="max-height: 90vh;">
                <div class="modal-header bg-maroon text-white">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="fa-solid fa-user-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editUserForm" novalidate class="d-flex flex-column flex-grow-1">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="modal-body" style="max-height: calc(90vh - 220px); overflow-y: auto;">
                        <div class="mb-3">
                            <label for="editFirstName" class="form-label">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editLastName" class="form-label">Last Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email Address <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                            <small class="form-text text-muted">Leave blank to keep current password</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editPasswordConfirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="editPasswordConfirmation"
                                name="password_confirmation">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="editRole" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Department Assignment (for Department Heads) -->
                        <div class="mb-3" id="editDepartmentField" style="display: none;">
                            <label for="editDepartment" class="form-label">
                                Department Assignment <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="editDepartment" name="department_id">
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Program Assignment (for Program Heads) -->
                        <div class="mb-3" id="editProgramField" style="display: none;">
                            <label for="editProgram" class="form-label">
                                Program Assignment <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="editProgram" name="program_id">
                                <option value="">Select Program</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                Assign this Program Head to manage a specific program
                            </small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-maroon">
                            <i class="fa-solid fa-save me-2"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-muted mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDeleteBtn">
                        <i class="fa-solid fa-trash me-2"></i>Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">
                        <i class="fa-solid fa-user-circle me-2"></i>User Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-3" id="viewUserAvatar"
                            style="width: 80px; height: 80px; font-size: 32px;">
                        </div>
                        <h4 class="mb-1" id="viewUserFullName"></h4>
                        <span class="badge status-badge" id="viewUserStatusBadge"></span>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-user me-2"></i>First Name
                                </div>
                                <div class="fw-semibold" id="viewUserFirstName"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-user me-2"></i>Last Name
                                </div>
                                <div class="fw-semibold" id="viewUserLastName"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-envelope me-2"></i>Email
                                </div>
                                <div class="fw-semibold" id="viewUserEmail"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="text-muted" style="min-width: 120px;">
                                    <i class="fa-solid fa-user-tag me-2"></i>Role
                                </div>
                                <div>
                                    <span class="badge bg-info" id="viewUserRole"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" id="viewUserEditBtn">
                        <i class="fa-solid fa-edit me-2"></i>Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggleStatusModalLabel">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>Confirm Status Change
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong id="toggleActionText"></strong> user <strong
                            id="toggleUserName"></strong>?</p>
                    <p class="text-muted mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i><span id="toggleStatusMessage"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmToggleStatusBtn">
                        <i class="fa-solid fa-toggle-on me-2"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS -->
    <style>
        /* ========================================
                                       MODAL LAYOUT FIX - Scrollable Modal Body
                                       ======================================== */

        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }

        /* Modal Dialog with height constraints */
        .modal-dialog {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        /* Modal Content - Flexbox layout */
        .modal-content {
            display: flex;
            flex-direction: column;
            height: auto;
            max-height: 90vh;
        }

        /* Modal Header - Fixed at top */
        .modal-header {
            flex-shrink: 0;
            background-color: #660000 !important;
            color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            color: #ffffff !important;
        }

        .modal-header .modal-title i {
            color: #ffffff !important;
        }

        /* Close button styling for dark backgrounds */
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        /* Modal Body - Scrollable */
        .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1.5rem;
            min-height: 200px;
        }

        /* Add smooth scrolling */
        .modal-body {
            scroll-behavior: smooth;
        }

        /* Scrollbar styling for modal body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Modal Footer - Fixed at bottom */
        .modal-footer {
            flex-shrink: 0;
            border-top: 1px solid #e9ecef;
            padding: 1rem;
            background-color: #ffffff;
        }

        /* Form inside modal - Flexbox layout */
        .modal-content form {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Ensure form body scrolls properly */
        .modal-content form .modal-body {
            max-height: calc(90vh - 220px);
            overflow-y: auto;
        }

        /* Prevent last form field from having unnecessary margin */
        .modal-body .mb-3:last-child {
            margin-bottom: 0.5rem;
        }

        /* ========================================
                                       MODAL BUTTON STYLING
                                       ======================================== */

        /* Primary action buttons within modals */
        .modal .btn-primary {
            background-color: #660000 !important;
            border-color: #660000 !important;
            color: #ffffff !important;
        }

        .modal .btn-primary:hover,
        .modal .btn-primary:focus,
        .modal .btn-primary:active {
            background-color: #520000 !important;
            border-color: #520000 !important;
            color: #ffffff !important;
        }

        .modal .btn-primary:disabled {
            background-color: #660000 !important;
            border-color: #660000 !important;
            opacity: 0.65;
        }

        /* ========================================
                                       UTILITY CLASSES
                                       ======================================== */

        .text-maroon {
            color: #660000 !important;
        }

        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: white;
        }

        .btn-maroon:hover {
            background-color: #880000;
            border-color: #880000;
            color: white;
        }

        .bg-maroon {
            background-color: #660000 !important;
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #660000;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .table-muted {
            opacity: 0.6;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(102, 0, 0, 0.05);
        }

        .status-badge {
            min-width: 75px;
            display: inline-block;
        }

        /* ========================================
                                       RESPONSIVE ADJUSTMENTS
                                       ======================================== */

        /* Tablet adjustments */
        @media (max-width: 768px) {
            .modal-content {
                max-height: calc(100vh - 20px);
            }

            .modal-body {
                max-height: calc(100vh - 240px) !important;
            }

            .modal-dialog {
                margin: 10px;
            }
        }

        /* Mobile adjustments */
        @media (max-width: 576px) {
            .modal-content {
                max-height: calc(100vh - 10px);
            }

            .modal-body {
                max-height: calc(100vh - 230px) !important;
                padding: 1rem;
            }

            .modal-footer {
                padding: 0.75rem;
            }

            .modal-footer .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .modal-title {
                font-size: 1rem;
            }
        }
    </style>

    <!-- JavaScript for User Management -->
    <script src="{{ asset('js/user-management.js') }}"></script>
@endsection
