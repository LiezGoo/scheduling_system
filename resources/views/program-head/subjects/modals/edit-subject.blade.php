<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="editSubjectModalLabel">
                    <i class="fa-solid fa-edit me-2"></i>Edit Subject
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="editSubjectForm" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_subject_id" name="subject_id">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_subject_code" class="form-label">
                                Subject Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_subject_code" name="subject_code"
                                required>
                            <div class="invalid-feedback">Please enter a subject code.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_subject_name" class="form-label">
                                Subject Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_subject_name" name="subject_name"
                                required>
                            <div class="invalid-feedback">Please enter a subject name.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_units" class="form-label">
                                Units <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_units" name="units" step="0.5"
                                min="0" max="10" required>
                            <div class="invalid-feedback">Please enter units (0-10).</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="edit_lecture_hours" class="form-label">
                                Lecture Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_lecture_hours" name="lecture_hours"
                                step="0.5" min="0" max="20" required>
                            <div class="invalid-feedback">Please enter lecture hours (0-20).</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="edit_lab_hours" class="form-label">
                                Lab Hours <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="edit_lab_hours" name="lab_hours"
                                step="0.5" min="0" max="20" required>
                            <div class="invalid-feedback">Please enter lab hours (0-20).</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_year_level" class="form-label">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="edit_year_level" name="year_level" required>
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                            <div class="invalid-feedback">Please select a year level.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="edit_semester" class="form-label">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="edit_semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                            </select>
                            <div class="invalid-feedback">Please select a semester.</div>
                        </div>
                    </div>

                    <div id="editSubjectAlert" class="alert d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon" id="editSubjectBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                        <span class="btn-text">Update Subject</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editSubjectForm = document.getElementById('editSubjectForm');
            const editSubjectBtn = document.getElementById('editSubjectBtn');
            const editSubjectAlert = document.getElementById('editSubjectAlert');
            const editSubjectModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));

            document.addEventListener('click', function(e) {
                if (e.target.closest('.edit-subject-btn')) {
                    const btn = e.target.closest('.edit-subject-btn');

                    document.getElementById('edit_subject_id').value = btn.dataset.subjectId;
                    document.getElementById('edit_subject_code').value = btn.dataset.subjectCode;
                    document.getElementById('edit_subject_name').value = btn.dataset.subjectName;
                    document.getElementById('edit_units').value = btn.dataset.units;
                    document.getElementById('edit_lecture_hours').value = btn.dataset.lectureHours;
                    document.getElementById('edit_lab_hours').value = btn.dataset.labHours;
                    document.getElementById('edit_year_level').value = btn.dataset.yearLevel;
                    document.getElementById('edit_semester').value = btn.dataset.semester;

                    editSubjectModal.show();
                }
            });

            editSubjectForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!editSubjectForm.checkValidity()) {
                    e.stopPropagation();
                    editSubjectForm.classList.add('was-validated');
                    return;
                }

                const subjectId = document.getElementById('edit_subject_id').value;
                const formData = new FormData(editSubjectForm);
                const spinner = editSubjectBtn.querySelector('.spinner-border');
                const btnText = editSubjectBtn.querySelector('.btn-text');

                editSubjectBtn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Updating...';

                fetch(`/program-head/subjects/${subjectId}`, {
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
                            editSubjectAlert.className = 'alert alert-success';
                            editSubjectAlert.textContent = data.message;
                            editSubjectAlert.classList.remove('d-none');

                            setTimeout(() => {
                                editSubjectModal.hide();
                                location.reload();
                            }, 1500);
                        } else {
                            editSubjectAlert.className = 'alert alert-danger';
                            editSubjectAlert.textContent = data.message || 'Failed to update subject.';
                            editSubjectAlert.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        editSubjectAlert.className = 'alert alert-danger';
                        editSubjectAlert.textContent = 'An error occurred. Please try again.';
                        editSubjectAlert.classList.remove('d-none');
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        editSubjectBtn.disabled = false;
                        spinner.classList.add('d-none');
                        btnText.textContent = 'Update Subject';
                    });
            });

            document.getElementById('editSubjectModal').addEventListener('hidden.bs.modal', function() {
                editSubjectForm.reset();
                editSubjectForm.classList.remove('was-validated');
                editSubjectAlert.classList.add('d-none');
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
