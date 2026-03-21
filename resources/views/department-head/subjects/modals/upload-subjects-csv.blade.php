<!-- Upload Subjects CSV Modal -->
<div class="modal fade" id="uploadSubjectsCsvModal" tabindex="-1" aria-labelledby="uploadSubjectsCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="uploadSubjectsCsvModalLabel">
                    <i class="fa-solid fa-file-csv me-2"></i>Upload Subjects CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadSubjectsCsvForm" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="fw-semibold mb-2">CSV Format</div>
                        <div class="small">
                            Required columns in exact order:<br>
                            <code>subject_code, subject_name, units, lecture_hours, lab_hours</code><br>
                            Rules: <code>units</code>, <code>lecture_hours</code>, and <code>lab_hours</code> must be whole numbers.
                        </div>
                    </div>

                    <div class="mb-3">
                        <a href="{{ route('department-head.subjects.csv-template') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-download me-2"></i>Download CSV Template
                        </a>
                    </div>

                    <div class="mb-3">
                        <label for="subjectsCsvFile" class="form-label fw-semibold">
                            CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="subjectsCsvFile" name="csv_file" accept=".csv,.txt" required>
                        <div class="form-text">Max file size: 2MB. Allowed extensions: .csv, .txt</div>
                        <div class="invalid-feedback">Please select a valid CSV file.</div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="failOnError" name="fail_on_error" value="1">
                        <label class="form-check-label" for="failOnError">
                            Fail entire upload when any row has an error
                        </label>
                    </div>

                    <div id="csvUploadResult" class="alert d-none mb-0"></div>
                    <div id="csvUploadErrorList" class="small mt-2 d-none" style="max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon" id="uploadSubjectsCsvBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Upload and Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('uploadSubjectsCsvForm');
        const uploadBtn = document.getElementById('uploadSubjectsCsvBtn');
        const spinner = uploadBtn.querySelector('.spinner-border');
        const btnText = uploadBtn.querySelector('.btn-text');
        const resultBox = document.getElementById('csvUploadResult');
        const errorList = document.getElementById('csvUploadErrorList');
        const modalEl = document.getElementById('uploadSubjectsCsvModal');
        const modal = new bootstrap.Modal(modalEl);

        function renderErrors(errors) {
            if (!Array.isArray(errors) || errors.length === 0) {
                errorList.classList.add('d-none');
                errorList.innerHTML = '';
                return;
            }

            const items = errors.map((entry) => {
                return `<li>Row ${entry.row}: ${entry.reason}</li>`;
            }).join('');

            errorList.innerHTML = `<div class="fw-semibold mb-1">Row Issues</div><ul class="mb-0 ps-3">${items}</ul>`;
            errorList.classList.remove('d-none');
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            form.classList.remove('was-validated');
            resultBox.className = 'alert d-none mb-0';
            resultBox.textContent = '';
            errorList.classList.add('d-none');
            errorList.innerHTML = '';

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const formData = new FormData(form);
            uploadBtn.disabled = true;
            spinner.classList.remove('d-none');
            btnText.textContent = 'Importing...';

            fetch('{{ route('department-head.subjects.upload-csv') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(async (response) => {
                const payload = await response.json();
                return { ok: response.ok, payload };
            })
            .then(({ ok, payload }) => {
                const inserted = payload.inserted ?? 0;
                const skipped = payload.skipped ?? 0;
                const summary = `Inserted: ${inserted}, Skipped/Failed: ${skipped}`;

                if (ok && payload.success) {
                    const message = payload.message ? `${payload.message} ${summary}` : summary;
                    resultBox.className = 'alert alert-success mb-0';
                    resultBox.textContent = message;
                    renderErrors(payload.errors || []);

                    if (window.showToast) {
                        window.showToast('success', message);
                    }

                    setTimeout(() => {
                        modal.hide();
                        window.location.reload();
                    }, 1200);
                    return;
                }

                const message = payload.message || 'CSV import failed.';
                resultBox.className = 'alert alert-danger mb-0';
                resultBox.textContent = `${message} ${summary}`;
                renderErrors(payload.errors || []);

                if (window.showToast) {
                    window.showToast('error', message);
                }
            })
            .catch((error) => {
                console.error(error);
                const message = 'An unexpected error occurred while importing CSV.';
                resultBox.className = 'alert alert-danger mb-0';
                resultBox.textContent = message;

                if (window.showToast) {
                    window.showToast('error', message);
                }
            })
            .finally(() => {
                uploadBtn.disabled = false;
                spinner.classList.add('d-none');
                btnText.textContent = 'Upload and Import';
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function() {
            form.reset();
            form.classList.remove('was-validated');
            resultBox.className = 'alert d-none mb-0';
            resultBox.textContent = '';
            errorList.classList.add('d-none');
            errorList.innerHTML = '';
        });
    });
</script>
@endpush
