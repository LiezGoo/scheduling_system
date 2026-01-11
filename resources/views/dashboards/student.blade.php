@extends('layouts.app')

@section('page-title', 'Student Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->full_name }}!</h5>
                                <p class="text-muted mb-0">Role: Student</p>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary">Learner View</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4" id="grades">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-calendar-days fa-2x text-secondary mb-2"></i>
                                        <h6 class="mb-1">My Schedule</h6>
                                        <p class="text-muted small mb-0">Daily classes and activities</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="curriculum-management">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-book-open-reader fa-2x text-primary mb-2"></i>
                                        <h6 class="mb-1">My Courses</h6>
                                        <p class="text-muted small mb-0">Materials, tasks, and updates</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="schedule-generation">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-chart-column fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">My Grades</h6>
                                        <p class="text-muted small mb-0">Track progress and performance</p>
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
