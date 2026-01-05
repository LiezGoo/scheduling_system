@extends('layouts.app')

@section('page-title', 'Program Head Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm" id="overview">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Welcome, {{ Auth::user()->name }}!</h5>
                                <p class="text-muted mb-0">Role: Program Head</p>
                            </div>
                            <span class="badge bg-info-subtle text-info">Program Oversight</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4" id="program-management">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-diagram-project fa-2x text-info mb-2"></i>
                                        <h6 class="mb-1">Program Courses</h6>
                                        <p class="text-muted small mb-0">Manage offerings and align outcomes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="faculty-load">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-users-line fa-2x text-success mb-2"></i>
                                        <h6 class="mb-1">Student Management</h6>
                                        <p class="text-muted small mb-0">Monitor enrollment and advisees</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4" id="schedule-generation">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fa-solid fa-chart-simple fa-2x text-warning mb-2"></i>
                                        <h6 class="mb-1">Program Reports</h6>
                                        <p class="text-muted small mb-0">Review KPIs and schedules</p>
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
