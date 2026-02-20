<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="addSubjectModalLabel">
                    <i class="fa-solid fa-book me-2"></i>Add New Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="addSubjectForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="add_department_id" class="form-label">
                                Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="add_department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a department.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_subject_code" class="form-label">
                                Subject Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="add_subject_code" name="subject_code"
                                required placeholder="e.g., CS101">
                            <div class="invalid-feedback">Please enter a subject code.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_subject_name" class="form-label">
                                Subject Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="add_subject_name" name="subject_name"
                                required placeholder="e.g., Introduction to Programming">
                            <div class="invalid-feedback">Please enter a subject name.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="add_units" class="form-label">
                                Units <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="add_units" name="units" step="0.5"
                                min="0" max="10" required placeholder="e.g., 3.0">
                            <div class="invalid-feedback">Please enter units.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="add_lecture_hours" class="form-label">
                                Lecture Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="add_lecture_hours" name="lecture_hours"
                                step="0.5" min="0" max="20" required placeholder="e.g., 3.0">
                            <div class="invalid-feedback">Please enter lecture hours.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="add_lab_hours" class="form-label">
                                Lab Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="add_lab_hours" name="lab_hours"
                                step="0.5" min="0" max="20" required placeholder="e.g., 0">
                            <div class="invalid-feedback">Please enter lab hours.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_year_level" class="form-label">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="add_year_level" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">Please select a year level.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="add_semester" class="form-label">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="add_semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                            </select>
                            <div class="invalid-feedback">Please select a semester.</div>
                        </div>
                    </div>

                    <!-- Alert for messages -->
                    <div id="addSubjectAlert" class="alert d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon" id="addSubjectBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                        <span class="btn-text">Save Subject</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addSubjectForm = document.getElementById('addSubjectForm');
            const addSubjectBtn = document.getElementById('addSubjectBtn');
            const addSubjectAlert = document.getElementById('addSubjectAlert');
            const addSubjectModal = new bootstrap.Modal(document.getElementById('addSubjectModal'));

            addSubjectForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!addSubjectForm.checkValidity()) {
                    e.stopPropagation();
                    addSubjectForm.classList.add('was-validated');
                    return;
                }

                const formData = new FormData(addSubjectForm);
                const spinner = addSubjectBtn.querySelector('.spinner-border');
                const btnText = addSubjectBtn.querySelector('.btn-text');

                addSubjectBtn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Saving...';

                fetch('{{ route('admin.subjects.store') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addSubjectAlert.className = 'alert alert-success';
                            addSubjectAlert.textContent = data.message;
                            addSubjectAlert.classList.remove('d-none');

                            setTimeout(() => {
                                addSubjectModal.hide();
                                location.reload();
                            }, 1500);
                        } else {
                            addSubjectAlert.className = 'alert alert-danger';
                            addSubjectAlert.textContent = data.message || 'Failed to create subject.';
                            addSubjectAlert.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        addSubjectAlert.className = 'alert alert-danger';
                        addSubjectAlert.textContent = 'An error occurred. Please try again.';
                        addSubjectAlert.classList.remove('d-none');
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        addSubjectBtn.disabled = false;
                        spinner.classList.add('d-none');
                        btnText.textContent = 'Save Subject';
                    });
            });

            // Reset form when modal is closed
            document.getElementById('addSubjectModal').addEventListener('hidden.bs.modal', function() {
                addSubjectForm.reset();
                addSubjectForm.classList.remove('was-validated');
                addSubjectAlert.classList.add('d-none');
            });
        });
    </script>
@endpush

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

    .bg-maroon {
        background-color: #660000;
    }
</style>
