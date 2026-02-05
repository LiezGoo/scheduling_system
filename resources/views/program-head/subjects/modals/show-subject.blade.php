<!-- View Subject Details Modal -->
<div class="modal fade" id="viewSubjectModal" tabindex="-1" aria-labelledby="viewSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="viewSubjectModalLabel">
                    <i class="fa-solid fa-book me-2"></i>Subject Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="subjectDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-maroon" role="status" aria-hidden="true">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewSubjectBtn = document.querySelectorAll('.view-subject-btn');
        const subjectDetailsContent = document.getElementById('subjectDetailsContent');
        const viewSubjectModal = document.getElementById('viewSubjectModal');

        viewSubjectBtn.forEach(btn => {
            btn.addEventListener('click', async function() {
                const subjectId = this.dataset.subjectId;

                try {
                    const response = await fetch(
                        `{{ route('program-head.subjects.index') }}/${subjectId}/details`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                    if (!response.ok) {
                        throw new Error('Failed to fetch subject details');
                    }

                    const data = await response.json();

                    if (data.success) {
                        const subject = data.subject;

                        // Build the details HTML
                        const detailsHTML = `
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Subject Code</h6>
                                            <p class="mb-0 fs-5 fw-semibold text-maroon">${escapeHtml(subject.subject_code)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Units</h6>
                                            <p class="mb-0 fs-5 fw-semibold">${parseFloat(subject.units).toFixed(2)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted text-uppercase small mb-2">Subject Name</h6>
                                        <p class="mb-0">${escapeHtml(subject.subject_name)}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Lecture Hours</h6>
                                            <p class="mb-0 fs-5 fw-semibold">${parseFloat(subject.lecture_hours).toFixed(1)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Lab Hours</h6>
                                            <p class="mb-0 fs-5 fw-semibold">${parseFloat(subject.lab_hours).toFixed(1)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Year Level</h6>
                                            <p class="mb-0 fs-5 fw-semibold">${getYearLevelLabel(subject.year_level)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <h6 class="text-muted text-uppercase small mb-2">Semester</h6>
                                            <p class="mb-0 fs-5 fw-semibold">${getSemesterLabel(subject.semester)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        subjectDetailsContent.innerHTML = detailsHTML;
                    } else {
                        subjectDetailsContent.innerHTML = `
                            <div class="alert alert-danger" role="alert">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>
                                ${escapeHtml(data.message || 'Failed to load subject details')}
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    subjectDetailsContent.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            An error occurred while loading subject details
                        </div>
                    `;
                }
            });
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Helper function to get year level label
        function getYearLevelLabel(yearLevel) {
            const labels = {
                '1': '1st Year',
                '2': '2nd Year',
                '3': '3rd Year',
                '4': '4th Year'
            };
            return labels[yearLevel] || 'N/A';
        }

        // Helper function to get semester label
        function getSemesterLabel(semester) {
            const labels = {
                '1': '1st Semester',
                '2': '2nd Semester'
            };
            return labels[semester] || 'N/A';
        }
    });
</script>
