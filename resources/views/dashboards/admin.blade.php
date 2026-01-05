@extends('layouts.app')

@section('page-title', 'Admin Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->name }}!</h5>
                                <p class="text-muted mb-0">Role: Administrator</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">System Overview</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-6 col-xl-3">
                                <div class="card border-0 shadow-sm h-100" id="user-management">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-users-gear fa-2x text-primary mb-2"></i>
                                        <h6 class="mb-1">Manage Users</h6>
                                        <p class="text-muted small mb-0">Roles, permissions, and access</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="faculty-load">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-clipboard-list fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">Faculty Load</h6>
                                        <p class="text-muted small mb-0">Teaching assignments and loads</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="program-management">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-diagram-project fa-2x text-info mb-2"></i>
                                        <h6 class="mb-1">Programs</h6>
                                        <p class="text-muted small mb-0">Track departments and programs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-3" id="schedule-generation">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-calendar-check fa-2x text-warning mb-2"></i>
                                        <h6 class="mb-1">Schedule Generation</h6>
                                        <p class="text-muted small mb-0">Build conflict-free timetables</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
