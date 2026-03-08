@extends('layouts.app')

@section('page-title', 'User Registration Approvals')

@push('styles')
<style>
    .approval-page-title {
        font-size: clamp(1.4rem, 2vw, 1.95rem);
        font-weight: 700;
        color: #3a3a3a;
        margin-bottom: 0.15rem;
    }

    .approval-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(102, 0, 0, 0.12);
        color: #660000;
        font-size: 1.3rem;
    }

    .approval-subtitle {
        color: #6c757d;
        margin: 0;
        font-size: 0.95rem;
    }

    .approval-stat-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(17, 24, 39, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .approval-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 22px rgba(17, 24, 39, 0.12);
    }

    .approval-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .approval-stat-value {
        font-size: clamp(1.55rem, 2.4vw, 2rem);
        font-weight: 700;
        line-height: 1;
        margin: 0 0 0.35rem;
    }

    .approval-stat-label {
        font-weight: 600;
        margin: 0;
    }

    .approval-stat-desc {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0;
    }

    .approval-stat-warning .approval-stat-icon {
        background: rgba(255, 193, 7, 0.15);
        color: #e0a800;
    }

    .approval-stat-success .approval-stat-icon {
        background: rgba(25, 135, 84, 0.14);
        color: #198754;
    }

    .approval-stat-danger .approval-stat-icon {
        background: rgba(220, 53, 69, 0.14);
        color: #dc3545;
    }

    .approval-content-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.09);
        overflow: hidden;
        background: #fff;
    }

    .approval-card-header {
        background: #fff;
        border-bottom: 1px solid #f0f1f4;
        padding: 1rem 1.25rem;
    }

    .approval-tabs {
        gap: 0.5rem;
    }

    .approval-tabs .nav-link {
        border: 0;
        border-radius: 999px;
        padding: 0.5rem 0.95rem;
        font-weight: 600;
        background: #f1f2f5;
        color: #495057;
        transition: all 0.2s ease;
    }

    .approval-tabs .nav-link:hover {
        background: #e5e7eb;
        color: #212529;
    }

    .approval-tabs .nav-link.active {
        background: #660000;
        color: #fff;
        box-shadow: 0 8px 16px rgba(102, 0, 0, 0.25);
    }

    .approval-tabs .nav-link .badge {
        font-size: 0.72rem;
        border-radius: 50rem;
        padding: 0.35rem 0.5rem;
    }

    .approval-quick-actions {
        border: 1px solid #eceef3;
        border-radius: 12px;
        padding: 0.9rem;
        background: #fafafb;
    }

    .approval-table thead th {
        background: #f8f9fb;
        color: #6c757d;
        border-bottom: 1px solid #eceef3;
    }

    .approval-user-avatar {
        width: 40px;
        height: 40px;
        background: #660000;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 0.82rem;
    }

    .approval-empty-state {
        border: 1px dashed #dfe3ea;
        border-radius: 14px;
        padding: 2rem 1rem;
        background: linear-gradient(180deg, #fcfcfd 0%, #f8f9fb 100%);
    }

    .approval-empty-state .icon {
        width: 74px;
        height: 74px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(102, 0, 0, 0.08);
        color: #660000;
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }

    .approval-skeleton {
        display: none;
        padding: 1rem 1.25rem 1.5rem;
    }

    .approval-skeleton.active {
        display: block;
    }

    .skeleton-line {
        border-radius: 8px;
        height: 14px;
        background: linear-gradient(90deg, #eceff3 25%, #e0e5ed 37%, #eceff3 63%);
        background-size: 400% 100%;
        animation: shimmer 1.25s infinite;
        margin-bottom: 0.75rem;
    }

    @keyframes shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: 0 0; }
    }

    .approval-btn {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .approval-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 14px rgba(17, 24, 39, 0.16);
    }

    .app-sidebar .nav-link.active {
        box-shadow: inset 3px 0 0 #ffffff, 0 0 0 1px rgba(255, 255, 255, 0.12), 0 0 10px rgba(102, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 767.98px) {
        .approval-card-header {
            padding: 0.85rem;
        }

        .approval-content-card .card-body {
            padding: 0.85rem;
        }

        .approval-tabs .nav-link {
            width: 100%;
            text-align: center;
        }
    }
</style>
@endpush

@section('content')
    @php
        $roleOptions = $users->getCollection()->pluck('role')->filter()->unique()->sort()->values();
        $departmentOptions = $users->getCollection()
            ->map(fn($user) => optional($user->department)->department_name)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $activeCount = $currentFilter === 'pending' ? $pendingCount : ($currentFilter === 'approved' ? $approvedCount : $rejectedCount);
    @endphp

    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <p class="approval-subtitle"><i class="fa-solid fa-user-check"></i> Review and manage account registration requests.</p>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card approval-stat-card approval-stat-warning h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="approval-stat-value text-warning">{{ $pendingCount }}</p>
                            <p class="approval-stat-label">Pending Requests</p>
                            <p class="approval-stat-desc">{{ $pendingCount }} users awaiting approval</p>
                        </div>
                        <div class="approval-stat-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card approval-stat-card approval-stat-success h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="approval-stat-value text-success">{{ $approvedCount }}</p>
                            <p class="approval-stat-label">Approved Users</p>
                            <p class="approval-stat-desc">{{ $approvedCount }} registrations approved</p>
                        </div>
                        <div class="approval-stat-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card approval-stat-card approval-stat-danger h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <p class="approval-stat-value text-danger">{{ $rejectedCount }}</p>
                            <p class="approval-stat-label">Rejected Users</p>
                            <p class="approval-stat-desc">{{ $rejectedCount }} requests were rejected</p>
                        </div>
                        <div class="approval-stat-icon">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card approval-content-card">
            <div class="approval-card-header">
                <ul class="nav nav-pills approval-tabs flex-wrap" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link approval-tab-link {{ $currentFilter === 'pending' ? 'active' : '' }}"
                           href="{{ route('admin.users.approvals', ['filter' => 'pending']) }}">
                            <i class="fa-solid fa-hourglass-half me-1"></i> Pending
                            <span class="badge {{ $currentFilter === 'pending' ? 'bg-light text-dark' : 'bg-warning text-dark' }} ms-1">{{ $pendingCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link approval-tab-link {{ $currentFilter === 'approved' ? 'active' : '' }}"
                           href="{{ route('admin.users.approvals', ['filter' => 'approved']) }}">
                            <i class="fa-solid fa-circle-check me-1"></i> Approved
                            <span class="badge {{ $currentFilter === 'approved' ? 'bg-light text-dark' : 'bg-success' }} ms-1">{{ $approvedCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link approval-tab-link {{ $currentFilter === 'rejected' ? 'active' : '' }}"
                           href="{{ route('admin.users.approvals', ['filter' => 'rejected']) }}">
                            <i class="fa-solid fa-circle-xmark me-1"></i> Rejected
                            <span class="badge {{ $currentFilter === 'rejected' ? 'bg-light text-dark' : 'bg-danger' }} ms-1">{{ $rejectedCount }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div id="approvalsSkeleton" class="approval-skeleton" aria-hidden="true">
                <div class="skeleton-line" style="height: 36px;"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line" style="width: 65%;"></div>
            </div>

            <div class="card-body">
                <div class="approval-quick-actions mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label for="approvalSearch" class="form-label small mb-1">Search Users</label>
                            <input type="text" id="approvalSearch" class="form-control" placeholder="Search by name or email">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="roleFilter" class="form-label small mb-1">Role</label>
                            <select id="roleFilter" class="form-select">
                                <option value="">All Roles</option>
                                @foreach ($roleOptions as $role)
                                    <option value="{{ strtolower($role) }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label for="departmentFilter" class="form-label small mb-1">Department</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="">All Departments</option>
                                @foreach ($departmentOptions as $departmentName)
                                    <option value="{{ strtolower($departmentName) }}">{{ $departmentName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="dateFilter" class="form-label small mb-1">Date Registered</label>
                            <input type="date" id="dateFilter" class="form-control">
                        </div>
                        <div class="col-6 col-lg-1 d-grid">
                            <button type="button" class="btn btn-outline-secondary approval-btn" id="resetFilters">
                                <i class="fa-solid fa-rotate-right me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2" id="filterResultText">Showing {{ $activeCount }} users</small>
                </div>

                @if ($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 approval-table">
                            <thead>
                                <tr>
                                    <th class="small fw-semibold">Full Name</th>
                                    <th class="small fw-semibold">Email</th>
                                    <th class="small fw-semibold">Requested Role</th>
                                    <th class="small fw-semibold">Date Registered</th>
                                    <th class="small fw-semibold text-center">Status</th>
                                    <th class="small fw-semibold text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="approvalTableBody">
                                @foreach ($users as $user)
                                    @php
                                        $departmentName = optional($user->department)->department_name ?? 'Unassigned';
                                    @endphp
                                    <tr class="approval-user-row"
                                        data-search="{{ strtolower($user->first_name . ' ' . $user->last_name . ' ' . $user->email) }}"
                                        data-role="{{ strtolower($user->role) }}"
                                        data-department="{{ strtolower($departmentName) }}"
                                        data-registered="{{ $user->created_at->format('Y-m-d') }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="approval-user-avatar">
                                                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                                    <div class="small text-muted">{{ $departmentName }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $user->email }}</code>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: rgba(102, 0, 0, 0.15); color: #660000;">
                                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fa-regular fa-calendar me-1"></i>
                                                {{ $user->created_at->format('M d, Y') }}
                                                <br>
                                                <i class="fa-regular fa-clock me-1"></i>
                                                {{ $user->created_at->format('h:i A') }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($user->approval_status === 'pending')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa-solid fa-hourglass-half me-1"></i>Pending
                                                </span>
                                            @elseif($user->approval_status === 'approved')
                                                <span class="badge bg-success">
                                                    <i class="fa-solid fa-check me-1"></i>Approved
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-times me-1"></i>Rejected
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                @if($user->approval_status === 'pending')
                                                    <button type="button"
                                                        class="btn btn-sm btn-success confirm-action approval-btn"
                                                        data-url="{{ route('admin.users.approve', $user) }}"
                                                        data-message="Are you sure you want to approve {{ $user->first_name }} {{ $user->last_name }}'s account?"
                                                        data-title="<i class='fa-solid fa-check-circle me-2'></i>Approve User"
                                                        data-btn-class="btn-maroon"
                                                        data-btn-text="<i class='fa-solid fa-check me-1'></i>Yes, Approve"
                                                        title="Approve this user account">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-danger approval-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal{{ $user->id }}"
                                                        title="Reject this user account">
                                                        <i class="fa-solid fa-times"></i>
                                                    </button>
                                                @elseif($user->approval_status === 'approved')
                                                    <button type="button" class="btn btn-sm btn-outline-primary approval-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewModal{{ $user->id }}"
                                                        title="View user details">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-danger approval-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewModal{{ $user->id }}"
                                                        title="View rejection details">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>

                                                    <button type="button"
                                                        class="btn btn-sm btn-success confirm-action approval-btn"
                                                        data-url="{{ route('admin.users.approve', $user) }}"
                                                        data-message="Are you sure you want to approve {{ $user->first_name }} {{ $user->last_name }}'s account?"
                                                        data-title="<i class='fa-solid fa-check-circle me-2'></i>Approve User"
                                                        data-btn-class="btn-success"
                                                        data-btn-text="<i class='fa-solid fa-check me-1'></i>Yes, Approve"
                                                        title="Approve this user">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if($user->approval_status === 'pending')
                                        <div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background: #660000; color: white;">
                                                        <h5 class="modal-title">
                                                            <i class="fa-solid fa-circle-xmark me-2"></i>
                                                            Reject User Registration
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <p class="mb-3">
                                                                Are you sure you want to reject the registration request from
                                                                <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>?
                                                            </p>
                                                            <div class="mb-3">
                                                                <label for="rejection_reason{{ $user->id }}" class="form-label">
                                                                    Rejection Reason <span class="text-danger">*</span>
                                                                </label>
                                                                <textarea class="form-control" id="rejection_reason{{ $user->id }}"
                                                                    name="rejection_reason"
                                                                    placeholder="Enter the reason for rejecting this registration..."
                                                                    rows="4" required></textarea>
                                                                <small class="text-muted">
                                                                    <i class="fa-solid fa-info-circle me-1"></i>
                                                                    This reason will be stored for administrative records.
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-top">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                                <i class="fa-solid fa-arrow-left me-1"></i>Cancel
                                                            </button>
                                                            <button type="submit" class="btn btn-danger approval-btn">
                                                                <i class="fa-solid fa-times me-1"></i>Reject Registration
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="modal fade" id="viewModal{{ $user->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background: #660000; color: white;">
                                                    <h5 class="modal-title">
                                                        <i class="fa-solid fa-user me-2"></i>
                                                        User Details
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong class="text-muted">Full Name:</strong><br>
                                                        {{ $user->first_name }} {{ $user->last_name }}
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong class="text-muted">Email:</strong><br>
                                                        <code>{{ $user->email }}</code>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong class="text-muted">Requested Role:</strong><br>
                                                        <span class="badge" style="background: rgba(102, 0, 0, 0.15); color: #660000;">
                                                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                                        </span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong class="text-muted">Registration Date:</strong><br>
                                                        {{ $user->created_at->format('F d, Y \a\t h:i A') }}
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong class="text-muted">Status:</strong><br>
                                                        @if($user->approval_status === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                            @if($user->approvedByAdmin)
                                                                <br><small class="text-muted">Approved by {{ $user->approvedByAdmin->first_name }} {{ $user->approvedByAdmin->last_name }}</small>
                                                                <br><small class="text-muted">on {{ $user->approved_at->format('M d, Y h:i A') }}</small>
                                                            @endif
                                                        @elseif($user->approval_status === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                            @if($user->rejected_at)
                                                                <br><small class="text-muted">Rejected on {{ $user->rejected_at->format('M d, Y h:i A') }}</small>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        @endif
                                                    </div>
                                                    @if($user->approval_status === 'rejected' && $user->rejection_reason)
                                                        <div class="mb-3">
                                                            <strong class="text-muted">Rejection Reason:</strong>
                                                            <div class="alert alert-danger mt-2 mb-0">
                                                                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                                                {{ $user->rejection_reason }}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fa-solid fa-times me-1"></i>Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="approval-empty-state text-center mt-3 d-none" id="filterEmptyState">
                        <div class="icon mx-auto"><i class="fa-solid fa-filter-circle-xmark"></i></div>
                        <h5 class="mb-2">No matching users</h5>
                        <p class="text-muted mb-3">Try adjusting search terms or clearing one of the active filters.</p>
                        <button type="button" class="btn btn-outline-secondary approval-btn" id="clearFilterEmptyBtn">
                            <i class="fa-solid fa-rotate-right me-1"></i>Reset Filters
                        </button>
                    </div>

                    <nav aria-label="Page navigation" class="mt-4">
                        {{ $users->links() }}
                    </nav>
                @else
                    <div class="approval-empty-state text-center">
                        <div class="icon mx-auto"><i class="fa-solid fa-envelope-open-text"></i></div>
                        <h4 class="mb-2">No {{ ucfirst($currentFilter) }} Registrations</h4>
                        <p class="text-muted mb-3">
                            @if($currentFilter === 'pending')
                                All user registration requests have already been reviewed.
                            @elseif($currentFilter === 'approved')
                                Approved accounts will appear here once registrations are accepted.
                            @else
                                Rejected account requests will appear here once decisions are made.
                            @endif
                        </p>
                        <a href="{{ route('admin.users.approvals', ['filter' => $currentFilter]) }}" class="btn btn-outline-secondary approval-btn">
                            <i class="fa-solid fa-rotate-right me-1"></i>Refresh List
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('approvalSearch');
        const roleFilter = document.getElementById('roleFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const dateFilter = document.getElementById('dateFilter');
        const resetFilters = document.getElementById('resetFilters');
        const clearFilterEmptyBtn = document.getElementById('clearFilterEmptyBtn');
        const rows = Array.from(document.querySelectorAll('.approval-user-row'));
        const emptyState = document.getElementById('filterEmptyState');
        const resultText = document.getElementById('filterResultText');
        const skeleton = document.getElementById('approvalsSkeleton');

        function normalize(value) {
            return (value || '').toString().trim().toLowerCase();
        }

        function applyFilters() {
            if (!rows.length) return;

            const searchValue = normalize(searchInput?.value);
            const roleValue = normalize(roleFilter?.value);
            const departmentValue = normalize(departmentFilter?.value);
            const dateValue = normalize(dateFilter?.value);

            let visibleCount = 0;

            rows.forEach((row) => {
                const rowSearch = normalize(row.dataset.search);
                const rowRole = normalize(row.dataset.role);
                const rowDepartment = normalize(row.dataset.department);
                const rowDate = normalize(row.dataset.registered);

                const matchesSearch = !searchValue || rowSearch.includes(searchValue);
                const matchesRole = !roleValue || rowRole === roleValue;
                const matchesDepartment = !departmentValue || rowDepartment === departmentValue;
                const matchesDate = !dateValue || rowDate === dateValue;

                const isVisible = matchesSearch && matchesRole && matchesDepartment && matchesDate;
                row.style.display = isVisible ? '' : 'none';

                if (isVisible) visibleCount += 1;
            });

            if (resultText) {
                resultText.textContent = `Showing ${visibleCount} user${visibleCount === 1 ? '' : 's'}`;
            }

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount !== 0);
            }
        }

        function clearAllFilters() {
            if (searchInput) searchInput.value = '';
            if (roleFilter) roleFilter.value = '';
            if (departmentFilter) departmentFilter.value = '';
            if (dateFilter) dateFilter.value = '';
            applyFilters();
        }

        [searchInput, roleFilter, departmentFilter, dateFilter].forEach((el) => {
            if (!el) return;
            const eventName = el.tagName === 'INPUT' && el.type !== 'date' ? 'input' : 'change';
            el.addEventListener(eventName, applyFilters);
        });

        if (resetFilters) {
            resetFilters.addEventListener('click', clearAllFilters);
        }

        if (clearFilterEmptyBtn) {
            clearFilterEmptyBtn.addEventListener('click', clearAllFilters);
        }

        document.querySelectorAll('.approval-tab-link, .pagination a').forEach((link) => {
            link.addEventListener('click', function () {
                if (skeleton) {
                    skeleton.classList.add('active');
                }
            });
        });

        document.querySelectorAll('.btn, .approval-stat-card, .approval-tabs .nav-link').forEach((node) => {
            node.style.transition = node.style.transition || 'all 0.2s ease';
        });

        applyFilters();
    });
</script>
@endpush
