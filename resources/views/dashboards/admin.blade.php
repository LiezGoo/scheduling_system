@extends('layouts.app')

@section('page-title', 'Home')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->full_name }}!</h5>
                                <p class="text-muted mb-0">Role: Admin</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">System Overview</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-6 col-xl-3">
                                <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 hover-card" id="user-management">
                                        <div class="card-body text-center">
                                            <i class="fa-solid fa-users-gear fa-2x text-primary mb-2"></i>
                                            <h6 class="mb-1 text-dark">Manage Users</h6>
                                            <p class="text-muted small mb-0">Roles, permissions, and access</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="faculty-load">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-clipboard-list fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">Department</h6>
                                        <p class="text-muted small mb-0">Manage Departments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="program-management">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-diagram-project fa-2x text-secondary mb-2"></i>
                                        <h6 class="mb-1">Programs</h6>
                                        <p class="text-muted small mb-0">Track departments and programs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="room-management">
                                <a href="{{ route('admin.rooms.index') }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 hover-card">
                                        <div class="card-body text-center">
                                            <i class="fa-solid fa-door-open fa-2x text-danger mb-2"></i>
                                            <h6 class="mb-1 text-dark">Rooms</h6>
                                            <p class="text-muted small mb-0">Manage campus rooms</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="academic-year-management">
                                <a href="{{ route('admin.academic-years.index') }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 hover-card">
                                        <div class="card-body text-center">
                                            <i class="fa-solid fa-calendar-alt fa-2x text-warning mb-2"></i>
                                            <h6 class="mb-1 text-dark">Academic Years</h6>
                                            <p class="text-muted small mb-0">Manage years & semesters</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(102, 0, 0, 0.15) !important;
        }
    </style>
@endsection
