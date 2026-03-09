<!-- Configure Faculty Workload Modal -->
<div class="modal fade" id="configureWorkloadModal" tabindex="-1" aria-labelledby="configureWorkloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content workload-modal">
            <div class="modal-header border-0">
                <div class="w-100 pe-4">
                    <h5 class="modal-title mb-1" id="configureWorkloadModalLabel">
                        <i class="fa-solid fa-hourglass-end me-2"></i>Configure Faculty Workload
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="configureWorkloadForm" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="config_id" id="configId">
                <input type="hidden" name="contract_type" id="contractTypeHidden" value="{{ $contractTypeOptions->first() ?? '' }}">

                <div class="modal-body bg-white">
                    <div class="alert alert-danger d-none mb-3" id="formAlert" role="alert"></div>

                    <section class="section-group mb-4">
                        <h6 class="section-title">Faculty Member</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="faculty" class="form-label">Faculty Member <span class="text-danger">*</span></label>
                                <select class="form-select" id="faculty" name="user_id" required>
                                    <option value="">-- Select Faculty Member --</option>
                                    @foreach ($facultyMembers as $faculty)
                                        <option value="{{ $faculty->id }}" data-contract-type="{{ $faculty->contract_type ?? '' }}">
                                            {{ $faculty->full_name }} - {{ $faculty->role }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-user_id">Please select a faculty member.</div>
                            </div>
                        </div>
                    </section>

                    <section class="section-group mb-4">
                        <h6 class="section-title">Teaching Load Limits</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="maxLectureHours" class="form-label">Maximum Lecture Hours per Week <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="maxLectureHours" name="max_lecture_hours" min="1" max="99" placeholder="e.g., 21" required>
                                <div class="invalid-feedback" id="error-max_lecture_hours">Maximum lecture hours must be greater than 0.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="maxLabHours" class="form-label">Maximum Laboratory Hours per Week <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="maxLabHours" name="max_lab_hours" min="0" max="99" placeholder="e.g., 12" required>
                                <div class="invalid-feedback" id="error-max_lab_hours">Maximum laboratory hours cannot be negative.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="maxHoursPerDay" class="form-label">Maximum Teaching Hours per Day <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="maxHoursPerDay" name="max_hours_per_day" min="1" max="24" placeholder="e.g., 6" required>
                                <div class="invalid-feedback" id="error-max_hours_per_day">Maximum teaching hours per day must be at least 1.</div>
                            </div>
                        </div>
                        <p class="form-text mt-3 mb-0">
                            These limits ensure that assigned subjects do not exceed the faculty's workload.
                        </p>
                    </section>

                    <section class="section-group mb-0">
                        <h6 class="section-title">Faculty Teaching Availability</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 teaching-scheme-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 140px;">Day</th>
                                        <th style="min-width: 170px;">Start Time</th>
                                        <th style="min-width: 170px;">End Time</th>
                                        <th class="text-center" style="min-width: 100px;">Enabled</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    @endphp
                                    @foreach ($days as $day)
                                        <tr data-day-row="{{ $day }}">
                                            <td class="fw-semibold">{{ $day }}</td>
                                            <td>
                                                <input
                                                    type="time"
                                                    class="form-control scheme-time"
                                                    id="schemeStart{{ $day }}"
                                                    name="teaching_scheme[{{ $day }}][start]"
                                                    data-day="{{ $day }}"
                                                    data-time-type="start"
                                                    disabled
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="time"
                                                    class="form-control scheme-time"
                                                    id="schemeEnd{{ $day }}"
                                                    name="teaching_scheme[{{ $day }}][end]"
                                                    data-day="{{ $day }}"
                                                    data-time-type="end"
                                                    disabled
                                                >
                                            </td>
                                            <td class="text-center">
                                                <input
                                                    class="form-check-input scheme-enabled"
                                                    type="checkbox"
                                                    id="schemeEnabled{{ $day }}"
                                                    name="teaching_scheme[{{ $day }}][enabled]"
                                                    value="1"
                                                    data-day="{{ $day }}"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="invalid-feedback d-block mt-2" id="teachingSchemeError" style="display: none;">
                            Please enable at least one day and set a valid start and end time.
                        </div>
                    </section>
                </div>

                <div class="modal-footer border-0 pt-3 mt-2 justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const configureModal = document.getElementById('configureWorkloadModal');
            const configureForm = document.getElementById('configureWorkloadForm');
            const facultySelect = document.getElementById('faculty');
            const contractTypeHidden = document.getElementById('contractTypeHidden');
            const formAlert = document.getElementById('formAlert');
            const teachingSchemeError = document.getElementById('teachingSchemeError');

            function getSchemeRow(day) {
                return {
                    enabled: document.getElementById(`schemeEnabled${day}`),
                    start: document.getElementById(`schemeStart${day}`),
                    end: document.getElementById(`schemeEnd${day}`),
                };
            }

            function setFacultyContractType() {
                const selectedOption = facultySelect.options[facultySelect.selectedIndex];
                const selectedContractType = selectedOption?.dataset?.contractType || '';
                const normalizedContractType = ['Full-Time', 'Part-Time', 'Contractual'].includes(selectedContractType)
                    ? selectedContractType
                    : 'Contractual';
                contractTypeHidden.value = normalizedContractType;
            }

            function toggleSchemeRow(day, isEnabled) {
                const row = getSchemeRow(day);

                row.start.disabled = !isEnabled;
                row.end.disabled = !isEnabled;
                row.start.required = !!isEnabled;
                row.end.required = !!isEnabled;

                if (isEnabled) {
                    if (!row.start.value) {
                        row.start.value = '07:00';
                    }
                    if (!row.end.value) {
                        row.end.value = '16:00';
                    }
                } else {
                    row.start.value = '';
                    row.end.value = '';
                    row.start.classList.remove('is-invalid');
                    row.end.classList.remove('is-invalid');
                }
            }

            function clearFieldErrors() {
                const errorIds = ['user_id', 'max_lecture_hours', 'max_lab_hours', 'max_hours_per_day'];
                errorIds.forEach((field) => {
                    const input = configureForm.querySelector(`[name="${field}"]`);
                    const error = document.getElementById(`error-${field}`);

                    if (input) {
                        input.classList.remove('is-invalid');
                    }
                    if (error) {
                        error.style.display = 'none';
                        error.textContent = '';
                    }
                });
            }

            function showFieldError(field, message) {
                const input = configureForm.querySelector(`[name="${field}"]`);
                const error = document.getElementById(`error-${field}`);

                if (input) {
                    input.classList.add('is-invalid');
                }
                if (error) {
                    error.textContent = message;
                    error.style.display = 'block';
                }
            }

            function renderServerErrors(errors) {
                clearFieldErrors();
                teachingSchemeError.style.display = 'none';

                Object.entries(errors || {}).forEach(([field, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;

                    if (field.startsWith('teaching_scheme')) {
                        teachingSchemeError.textContent = message || 'Please enable at least one day and set a valid start and end time.';
                        teachingSchemeError.style.display = 'block';
                        return;
                    }

                    showFieldError(field, message || 'Invalid value.');
                });
            }

            function resetTeachingScheme() {
                weekDays.forEach(day => {
                    const row = getSchemeRow(day);
                    row.enabled.checked = false;
                    toggleSchemeRow(day, false);
                });
                teachingSchemeError.style.display = 'none';
                teachingSchemeError.textContent = 'Please enable at least one day and set a valid start and end time.';
            }

            function validateTeachingScheme() {
                let hasEnabledDay = false;
                let hasInvalidRange = false;

                weekDays.forEach(day => {
                    const row = getSchemeRow(day);
                    row.start.classList.remove('is-invalid');
                    row.end.classList.remove('is-invalid');

                    if (!row.enabled.checked) {
                        return;
                    }

                    hasEnabledDay = true;

                    if (!row.start.value || !row.end.value || row.start.value >= row.end.value) {
                        hasInvalidRange = true;
                        row.start.classList.add('is-invalid');
                        row.end.classList.add('is-invalid');
                    }
                });

                if (!hasEnabledDay || hasInvalidRange) {
                    teachingSchemeError.style.display = 'block';
                    teachingSchemeError.textContent = !hasEnabledDay
                        ? 'Please enable at least one day and set a valid start and end time.'
                        : 'Start time must be earlier than end time.';
                    return false;
                }

                teachingSchemeError.style.display = 'none';
                teachingSchemeError.textContent = 'Please enable at least one day and set a valid start and end time.';
                return true;
            }

            function applyTeachingScheme(configuration) {
                resetTeachingScheme();

                const scheme = configuration?.teaching_scheme || {};
                const availableDays = Array.isArray(configuration?.available_days) ? configuration.available_days : [];

                weekDays.forEach(day => {
                    const row = getSchemeRow(day);
                    const dayScheme = scheme[day] || null;
                    const isEnabled = !!dayScheme || availableDays.includes(day);

                    row.enabled.checked = isEnabled;
                    toggleSchemeRow(day, isEnabled);

                    if (dayScheme) {
                        if (dayScheme.start) {
                            row.start.value = String(dayScheme.start).substring(0, 5);
                        }
                        if (dayScheme.end) {
                            row.end.value = String(dayScheme.end).substring(0, 5);
                        }
                    }
                });
            }

            weekDays.forEach(day => {
                const row = getSchemeRow(day);
                row.enabled.addEventListener('change', function() {
                    toggleSchemeRow(day, this.checked);
                    if (this.checked) {
                        teachingSchemeError.style.display = 'none';
                    }
                });
            });

            facultySelect.addEventListener('change', function() {
                setFacultyContractType();
                if (this.value) {
                    this.classList.remove('is-invalid');
                    const facultyError = document.getElementById('error-user_id');
                    if (facultyError) {
                        facultyError.style.display = 'none';
                    }
                }
            });

            configureForm.addEventListener('submit', function(e) {
                e.preventDefault();

                formAlert.classList.add('d-none');
                formAlert.innerHTML = '';
                clearFieldErrors();

                if (!validateTeachingScheme()) {
                    scrollToElement(teachingSchemeError);
                    return;
                }

                if (!this.checkValidity()) {
                    this.classList.add('was-validated');
                    scrollToFirstInvalidControl();
                    return;
                }

                setFacultyContractType();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';

                const formData = new FormData(this);
                const configId = document.getElementById('configId').value;
                const isEdit = !!configId;
                const method = isEdit ? 'PUT' : 'POST';
                const url = isEdit
                    ? `/program-head/faculty-workload-configurations/${configId}`
                    : '/program-head/faculty-workload-configurations';

                fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(async (response) => {
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw {
                            status: response.status,
                            payload: payload,
                        };
                    }
                    return payload;
                })
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(configureModal);
                        modal.hide();

                        if (typeof window.refreshFacultyWorkloadTable === 'function') {
                            window.refreshFacultyWorkloadTable({ silent: true })
                                .then((refreshed) => {
                                    if (!refreshed) {
                                        console.warn('Table refresh failed after save.');
                                    }
                                });
                        }

                        showToast('success', data.message || (isEdit ? 'Faculty workload configuration updated successfully!' : 'Faculty workload configuration saved successfully!'));
                    } else {
                        formAlert.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>
                                <div>${data.message || 'An error occurred. Please try again.'}</div>
                            </div>
                        `;
                        formAlert.classList.remove('d-none');
                        scrollToElement(formAlert);
                    }
                })
                .catch(error => {
                    if (error?.status === 422 && error?.payload?.errors) {
                        renderServerErrors(error.payload.errors);
                        if (error.payload.message) {
                            formAlert.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                                    <div>${error.payload.message}</div>
                                </div>
                            `;
                            formAlert.classList.remove('d-none');
                        }
                        scrollToFirstInvalidControl();
                        return;
                    }

                    formAlert.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <div>An error occurred. Please try again.</div>
                        </div>
                    `;
                    formAlert.classList.remove('d-none');
                    scrollToElement(formAlert);
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });

            configureModal.addEventListener('hidden.bs.modal', function() {
                configureForm.reset();
                configureForm.classList.remove('was-validated');
                formAlert.classList.add('d-none');
                formAlert.innerHTML = '';
                document.getElementById('configId').value = '';
                facultySelect.disabled = false;
                contractTypeHidden.value = 'Contractual';
                clearFieldErrors();
                resetTeachingScheme();
            });

            configureModal.addEventListener('show.bs.modal', function() {
                configureForm.classList.remove('was-validated');
                formAlert.classList.add('d-none');
                formAlert.innerHTML = '';
                clearFieldErrors();
                teachingSchemeError.style.display = 'none';
            });

            function scrollToElement(element) {
                const modalBody = configureModal.querySelector('.modal-body');
                if (modalBody && element) {
                    setTimeout(() => {
                        element.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 100);
                }
            }

            function scrollToFirstInvalidControl() {
                const invalidControl = configureForm.querySelector('.is-invalid, :invalid');
                if (invalidControl) {
                    scrollToElement(invalidControl);
                }
            }

            window.populateConfigureForm = function(configuration, isEdit = false) {
                const form = document.getElementById('configureWorkloadForm');
                const modal = document.getElementById('configureWorkloadModal');
                const modalTitle = modal.querySelector('.modal-title');

                if (isEdit) {
                    modalTitle.innerHTML = '<i class="fa-solid fa-hourglass-end me-2"></i>Edit Faculty Workload Configuration';
                    form.dataset.method = 'PUT';
                    form.dataset.configId = configuration.id;

                    document.getElementById('faculty').value = configuration.user_id;
                    document.getElementById('faculty').disabled = true;
                    document.getElementById('contractTypeHidden').value = configuration.contract_type || '{{ $contractTypeOptions->first() ?? '' }}';
                    document.getElementById('maxLectureHours').value = configuration.max_lecture_hours;
                    document.getElementById('maxLabHours').value = configuration.max_lab_hours;
                    document.getElementById('maxHoursPerDay').value = configuration.max_hours_per_day;
                    document.getElementById('configId').value = configuration.id;

                    applyTeachingScheme(configuration);
                } else {
                    modalTitle.innerHTML = '<i class="fa-solid fa-hourglass-end me-2"></i>Configure Faculty Workload';
                    form.dataset.method = 'POST';
                    delete form.dataset.configId;
                    form.reset();
                    document.getElementById('faculty').disabled = false;
                    document.getElementById('contractTypeHidden').value = 'Contractual';
                    document.getElementById('configId').value = '';
                    resetTeachingScheme();
                }
            };

            resetTeachingScheme();
        });
    </script>
@endpush

@push('styles')
    <style>
        #configureWorkloadModal .modal-header {
            background-color: #660000;
            color: #ffffff;
        }

        #configureWorkloadModal .modal-footer {
            background-color: #ffffff;
            color: #212529;
        }

        #configureWorkloadModal .modal-title {
            color: #ffffff;
            font-weight: 600;
        }

        #configureWorkloadModal .modal-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Force reliable scroll behavior for long modal content. */
        #configureWorkloadModal .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 2rem);
        }

        #configureWorkloadModal .modal-dialog-scrollable .modal-body {
            max-height: calc(100vh - 220px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        #configureWorkloadModal .btn-close {
            filter: invert(1);
            opacity: 0.9;
        }

        #configureWorkloadModal .btn-close:hover {
            opacity: 1;
        }

        #configureWorkloadModal .section-title {
            color: #800000;
            font-size: 0.95rem;
            font-weight: 700;
            border-bottom: 2px solid #800000;
            padding-bottom: 0.45rem;
            margin-bottom: 1rem;
        }

        #configureWorkloadModal .form-label {
            font-weight: 500;
        }

        #configureWorkloadModal .form-control:focus,
        #configureWorkloadModal .form-select:focus {
            border-color: #800000;
            box-shadow: 0 0 0 0.2rem rgba(128, 0, 0, 0.12);
        }

        #configureWorkloadModal .btn-maroon {
            background-color: #800000;
            border-color: #800000;
            color: #ffffff;
        }

        #configureWorkloadModal .btn-maroon:hover,
        #configureWorkloadModal .btn-maroon:focus {
            background-color: #660000;
            border-color: #660000;
            color: #ffffff;
        }

        #configureWorkloadModal .teaching-scheme-table th,
        #configureWorkloadModal .teaching-scheme-table td {
            vertical-align: middle;
        }

        #configureWorkloadModal .scheme-enabled {
            border-color: #800000;
            cursor: pointer;
        }

        #configureWorkloadModal .scheme-enabled:checked {
            background-color: #800000;
            border-color: #800000;
        }

        #configureWorkloadModal .invalid-feedback {
            display: none;
        }

        #configureWorkloadModal .form-control.is-invalid + .invalid-feedback,
        #configureWorkloadModal .form-select.is-invalid + .invalid-feedback {
            display: block;
        }

        @media (max-width: 768px) {
            #configureWorkloadModal .teaching-scheme-table {
                min-width: 620px;
            }
        }
    </style>
@endpush
