@extends('layouts.app')

@section('title', 'User Approvals')

@section('content')
    <div class="container-fluid py-4">
        {{-- Header Section --}}
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fa-solid fa-clipboard-check text-primary"></i> Pending User Approvals
                        </h1>
                        <p class="text-muted small mb-0 mt-1">Manage pending user registrations</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted small mb-1">Pending Approvals</p>
                                <h3 class="mb-0" style="color: #660000;">{{ $stats['total_pending'] }}</h3>
                            </div>
                            <div style="background: rgba(102, 0, 0, 0.1); border-radius: 8px; padding: 12px;">
                                <i class="fa-solid fa-hourglass-end" style="color: #660000; font-size: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted small mb-1">Approved Users</p>
                                <h3 class="mb-0 text-success">{{ $stats['total_approved'] }}</h3>
                            </div>
                            <div style="background: rgba(40, 167, 69, 0.1); border-radius: 8px; padding: 12px;">
                                <i class="fa-solid fa-check-circle text-success" style="font-size: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted small mb-1">Total Users</p>
                                <h3 class="mb-0">{{ $stats['total_pending'] + $stats['total_approved'] }}</h3>
                            </div>
                            <div style="background: rgba(0, 123, 255, 0.1); border-radius: 8px; padding: 12px;">
                                <i class="fa-solid fa-users text-primary" style="font-size: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Pending Users Table --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0">
                    <i class="fa-solid fa-list me-2"></i>Pending Approval Requests
                </h5>
            </div>

            <div class="card-body">
                @if ($pendingUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-muted small fw-semibold">Full Name</th>
                                    <th class="text-muted small fw-semibold">Email</th>
                                    <th class="text-muted small fw-semibold">Role</th>
                                    <th class="text-muted small fw-semibold">Registered</th>
                                    <th class="text-muted small fw-semibold text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pendingUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 40px; height: 40px; background: #660000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $user->email }}</code>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: rgba(102, 0, 0, 0.2); color: #660000;">
                                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $user->created_at->format('M d, Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                {{-- Approve Button --}}
                                                <form method="POST" action="{{ route('admin.users.approve', $user) }}" 
                                                    style="display: inline;"
                                                    onsubmit="return confirm('Approve {{ $user->first_name }} {{ $user->last_name }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                        title="Approve this user account">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>

                                                {{-- Reject Button --}}
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal{{ $user->id }}"
                                                    title="Reject this user account">
                                                    <i class="fa-solid fa-times"></i> Reject
                                                </button>

                                                {{-- Reject Modal --}}
                                                <div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header border-bottom">
                                                                <h5 class="modal-title">
                                                                    <i class="fa-solid fa-circle-xmark text-danger me-2"></i>
                                                                    Reject User Account
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                                                @csrf
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to reject
                                                                        <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>'s
                                                                        account?
                                                                    </p>
                                                                    <div class="mb-3">
                                                                        <label for="reason{{ $user->id }}" class="form-label">
                                                                            Reason (optional)
                                                                        </label>
                                                                        <textarea class="form-control" id="reason{{ $user->id }}"
                                                                            name="reason" placeholder="Enter reason for rejection..."
                                                                            rows="3"></textarea>
                                                                        <small class="text-muted">This reason will not be shared with the user.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-top">
                                                                    <button type="button" class="btn btn-outline-secondary"
                                                                        data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">
                                                                        <i class="fa-solid fa-times me-1"></i>Reject Account
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <nav aria-label="Page navigation" class="mt-4">
                        {{ $pendingUsers->links() }}
                    </nav>
                @else
                    <div class="text-center py-5">
                        <div style="font-size: 48px; color: #ddd; margin-bottom: 12px;">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <h5 class="text-muted">No Pending Approvals</h5>
                        <p class="text-muted small mb-0">All users have been reviewed. Check back later for new registrations.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
