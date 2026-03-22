<!-- Upload CSV Modal -->
<div class="modal fade" id="uploadRoomCsvModal" tabindex="-1" aria-labelledby="uploadRoomCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" id="uploadRoomCsvModalLabel" style="font-weight: 600;">
                    <i class="fas fa-file-upload me-2"></i> Upload Rooms via CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="uploadRoomCsvForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="rooms_csv_file" class="form-label fw-bold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="rooms_csv_file" name="file" accept=".csv,text/csv" required>
                        <div class="form-text">
                            Required column order: room_code,room_name,building,floor,capacity,type
                        </div>
                        <div class="invalid-feedback">Please select a valid non-empty CSV file.</div>
                    </div>
                </form>

                <div id="uploadCsvResult" class="d-none">
                    <hr>
                    <div id="uploadCsvSummary" class="alert mb-3" role="alert"></div>
                    <div>
                        <h6 class="fw-bold mb-2">Failed Rows</h6>
                        <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Line</th>
                                        <th>Room Code</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody id="uploadCsvFailedRowsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-maroon" id="uploadRoomCsvBtn" onclick="submitRoomCsvUpload()">
                    <i class="fas fa-cloud-upload-alt me-2"></i><span id="uploadRoomCsvBtnText">Upload CSV</span>
                    <span id="uploadRoomCsvSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                        aria-hidden="true" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetRoomCsvUploadResult() {
        const resultWrapper = document.getElementById('uploadCsvResult');
        const summary = document.getElementById('uploadCsvSummary');
        const failedBody = document.getElementById('uploadCsvFailedRowsBody');

        resultWrapper.classList.add('d-none');
        summary.className = 'alert mb-3';
        summary.textContent = '';
        failedBody.innerHTML = '';
    }

    function renderRoomCsvUploadResult(data) {
        const resultWrapper = document.getElementById('uploadCsvResult');
        const summary = document.getElementById('uploadCsvSummary');
        const failedBody = document.getElementById('uploadCsvFailedRowsBody');

        const summaryData = data.summary || {};
        const failedRows = Array.isArray(data.failed_rows) ? data.failed_rows : [];
        const summaryMessage = `${data.message || 'CSV upload completed.'} ` +
            `Total: ${summaryData.total_rows ?? 0}, Success: ${summaryData.successful_imports ?? 0}, Failed: ${summaryData.failed_rows ?? failedRows.length}.`;

        const hasFailures = failedRows.length > 0;
        summary.className = `alert mb-3 ${hasFailures ? 'alert-warning' : 'alert-success'}`;
        summary.textContent = summaryMessage;

        if (hasFailures) {
            failedBody.innerHTML = failedRows.map(row => {
                const line = row.line ?? 'N/A';
                const roomCode = row.room_code || 'N/A';
                const reason = row.reason || 'Unknown error';
                return `<tr><td>${escapeHtml(line)}</td><td>${escapeHtml(roomCode)}</td><td>${escapeHtml(reason)}</td></tr>`;
            }).join('');
        } else {
            failedBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No failed rows.</td></tr>';
        }

        resultWrapper.classList.remove('d-none');
    }

    async function submitRoomCsvUpload() {
        const form = document.getElementById('uploadRoomCsvForm');
        const fileInput = document.getElementById('rooms_csv_file');
        const uploadBtn = document.getElementById('uploadRoomCsvBtn');
        const uploadSpinner = document.getElementById('uploadRoomCsvSpinner');

        resetRoomCsvUploadResult();

        if (!fileInput.files.length) {
            form.classList.add('was-validated');
            window.showToast('Please select a CSV file first.', 'warning');
            return;
        }

        const selectedFile = fileInput.files[0];
        if (!selectedFile || selectedFile.size === 0) {
            form.classList.add('was-validated');
            window.showToast('Selected file is empty. Please choose a valid CSV file.', 'warning');
            return;
        }

        form.classList.remove('was-validated');
        uploadBtn.disabled = true;
        uploadSpinner.style.display = 'inline-block';

        try {
            const formData = new FormData();
            formData.append('file', selectedFile);

            const response = await fetch('{{ route('admin.rooms.upload') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : {
                    status: 'error',
                    message: 'Server returned an invalid response format.',
                    summary: {
                        total_rows: 0,
                        successful_imports: 0,
                        failed_rows: 0,
                    },
                    failed_rows: [],
                };

            if (response.ok) {
                renderRoomCsvUploadResult(data);
                window.showToast(data.message || 'Rooms uploaded successfully.', 'success');

                if (data.summary && Number(data.summary.successful_imports) > 0) {
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                }
                return;
            }

            renderRoomCsvUploadResult(data);
            window.showToast(data.message || 'CSV upload failed.', 'error');
        } catch (error) {
            console.error('Room CSV upload error:', error);
            window.showToast('An unexpected error occurred while uploading CSV.', 'error');
        } finally {
            uploadBtn.disabled = false;
            uploadSpinner.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('uploadRoomCsvModal');
        modalElement.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('uploadRoomCsvForm');
            form.reset();
            form.classList.remove('was-validated');
            resetRoomCsvUploadResult();
        });
    });
</script>
