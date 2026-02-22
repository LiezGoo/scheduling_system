@extends('layouts.app')

@section('page-title', 'Subject Details')

@section('content')
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.subjects.index') }}">Subjects</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $subject->subject_code }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ $subject->subject_name }}</h1>
            </div>
            <div>
                <button type="button" class="btn btn-outline-warning edit-subject-btn" data-subject-id="{{ $subject->id }}"
                    data-subject-code="{{ $subject->subject_code }}" data-subject-name="{{ $subject->subject_name }}"
                    data-department-id="{{ $subject->department_id }}" data-units="{{ $subject->units }}"
                    data-lecture-hours="{{ $subject->lecture_hours }}" data-lab-hours="{{ $subject->lab_hours }}"
                    data-description="{{ $subject->description }}"
                    data-is-active="{{ $subject->is_active ? 'true' : 'false' }}"
                    data-bs-toggle="modal" data-bs-target="#editSubjectModal">
                    <i class="fa-solid fa-edit me-2"></i>Edit Subject
                </button>
            </div>
        </div>

        <!-- Subject Information Card -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Subject Code</label>
                        <p class="mb-0 fw-bold fs-5">{{ $subject->subject_code }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Subject Name</label>
                        <p class="mb-0 fs-5">{{ $subject->subject_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Department</label>
                        <p class="mb-0">{{ $subject->department->department_name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Type</label>
                        <p class="mb-0">{{ $subject->computed_type ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Description</label>
                        <p class="mb-0">{{ $subject->description ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <p class="mb-0">
                            @if ($subject->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Units</label>
                        <p class="mb-0">{{ $subject->units }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Lecture Hours</label>
                        <p class="mb-0">{{ $subject->lecture_hours }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Lab Hours</label>
                        <p class="mb-0">{{ $subject->lab_hours }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.subjects.modals.edit-subject')
@endsection

@push('styles')
    <style>
        .btn-maroon {
            background-color: #660000;
            border-color: #660000;
            color: #fff;
        }

        .btn-maroon:hover {
            background-color: #550000;
            border-color: #550000;
            color: #fff;
        }

        .text-maroon {
            color: #660000;
        }

        .bg-maroon {
            background-color: #660000;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const department = @json($subject->department);
    </script>
@endpush
