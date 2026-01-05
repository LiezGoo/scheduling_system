@extends('layouts.app')

@section('page-title', 'Department Head Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->name }}!</h5>
                                <p class="text-muted mb-0">Role: Department Head</p>
                            </div>
                            <span class="badge bg-success-subtle text-success">Department Overview</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4" id="faculty-load">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-person-chalkboard fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">Manage Faculty</h6>
                                        <p class="text-muted small mb-0">Assign loads and review performance</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="curriculum-management">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-book-open fa-2x text-primary mb-2"></i>
                                        <h6 class="mb-1">Course Management</h6>
                                        <p class="text-muted small mb-0">Curriculum alignment across programs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="schedule-generation">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-calendar-check fa-2x text-warning mb-2"></i>
                                        <h6 class="mb-1">Schedule Approval</h6>
                                        <p class="text-muted small mb-0">Validate conflict-free timetables</p>
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
