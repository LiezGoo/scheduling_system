@extends('layouts.app')

@section('page-title', 'Instructor Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->full_name }}!</h5>
                                <p class="text-muted mb-0">Role: Instructor</p>
                            </div>
                            <span class="badge bg-warning-subtle text-warning">Teaching Overview</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4" id="schedule-generation">
                                <a href="{{ route('instructor.my-schedule.index') }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 hover-card">
                                        <div class="card-body text-center">
                                            <i class="fa-solid fa-calendar-days fa-2x text-warning mb-2"></i>
                                            <h6 class="mb-1">My Schedule</h6>
                                            <p class="text-muted small mb-0">See classes and meetings</p> 
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 col-md-4" id="curriculum-management">
                                <a href="{{ route('instructor.my-loads.index') }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 hover-card">
                                        <div class="card-body text-center">
                                            <i class="fa-solid fa-book-open-reader fa-2x text-primary mb-2"></i>
                                            <h6 class="mb-1">My loads</h6>
                                            <p class="text-muted small mb-0">Materials, syllabi, and updates</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 col-md-4" id="faculty-load">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-user-graduate fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">My Students</h6>
                                        <p class="text-muted small mb-0">Attendance and performance</p>
                                    </div>
                                </div>
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
