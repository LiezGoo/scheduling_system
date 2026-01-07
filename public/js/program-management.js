/**
 * Program Management JavaScript
 * Handles filters and AJAX CRUD (ready for integration)
 */

(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Modals
    const addProgramModalEl = document.getElementById('addProgramModal');
    const editProgramModalEl = document.getElementById('editProgramModal');
    const deleteProgramModalEl = document.getElementById('deleteProgramModal');
    const addProgramModal = addProgramModalEl ? new bootstrap.Modal(addProgramModalEl) : null;
    const editProgramModal = editProgramModalEl ? new bootstrap.Modal(editProgramModalEl) : null;
    const deleteProgramModal = deleteProgramModalEl ? new bootstrap.Modal(deleteProgramModalEl) : null;

    // Forms
    const filterForm = document.getElementById('programFilterForm');
    const addForm = document.getElementById('addProgramForm');
    const editForm = document.getElementById('editProgramForm');

    // Targets
    const tableBody = document.getElementById('programsTableBody');
    const pagination = document.getElementById('programsPagination');
    const summary = document.getElementById('programsSummary');
    const clearBtn = document.getElementById('clearProgramFilters');
    const spinner = document.getElementById('programsFiltersSpinner');

    // Utils
    const debounce = (fn, delay = 400) => {
      let t;
      return (...args) => {
        window.clearTimeout(t);
        t = window.setTimeout(() => fn(...args), delay);
      };
    };

    function showToast(type, message) {
      let toastContainer = document.querySelector('.toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
      }
      const toastId = 'toast-' + Date.now();
      const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
      const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
      const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body"><i class="fa-solid ${iconClass} me-2"></i>${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>`;
      toastContainer.insertAdjacentHTML('beforeend', toastHTML);
      const toastElement = document.getElementById(toastId);
      const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
      toast.show();
      toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    }

    const toggleSpinner = (show) => spinner && spinner.classList.toggle('d-none', !show);

    // Filtering
    const applyFilters = (opts = {}) => {
      if (!filterForm) return;
      const formData = new FormData(filterForm);
      const params = new URLSearchParams();
      formData.forEach((val, key) => {
        if (val && val.toString().trim() !== '') params.append(key, val.toString().trim());
      });
      if (opts.page) params.set('page', opts.page);
      const baseUrl = filterForm.dataset.listUrl || filterForm.action;
      const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
      toggleSpinner(true);
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
          if (!data.success) throw new Error('Filter failed');
          if (tableBody) tableBody.innerHTML = data.rows;
          if (pagination) pagination.innerHTML = data.pagination;
          if (summary) summary.innerHTML = data.summary;
        })
        .catch((e) => console.error('Program filter error:', e))
        .finally(() => toggleSpinner(false));
    };
    const debouncedApplyFilters = debounce(() => applyFilters(), 400);

    if (filterForm) {
      filterForm.addEventListener('submit', (e) => e.preventDefault());
      filterForm.querySelectorAll('input[type="text"], input[type="search"], select').forEach((el) => {
        if (el.tagName === 'SELECT') el.addEventListener('change', () => applyFilters());
        else el.addEventListener('input', () => debouncedApplyFilters());
      });
      clearBtn?.addEventListener('click', () => { filterForm.reset(); applyFilters(); });
    }

    document.addEventListener('click', (e) => {
      const link = e.target.closest('#programsPagination a');
      if (!link) return;
      e.preventDefault();
      const url = new URL(link.href);
      applyFilters({ page: url.searchParams.get('page') || 1 });
    });

    // CRUD (AJAX endpoints assumed to exist at /admin/programs)
    // Create
    if (addForm && addProgramModal) {
      addForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearValidationErrors(addForm);
        const submitBtn = addForm.querySelector('button[type="submit"]');
        const originalHTML = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...';

        const formData = new FormData(addForm);
        fetch('/admin/programs', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
          body: formData,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              showToast('success', data.message || 'Program created successfully');
              addProgramModal.hide();
              addForm.reset();
              applyFilters();
            } else if (data.errors) {
              displayValidationErrors(addForm, data.errors);
            } else {
              showToast('error', data.message || 'Failed to create program');
            }
          })
          .catch(() => showToast('error', 'An unexpected error occurred'))
          .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = originalHTML; });
      });
    }

    // Load program for edit
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.edit-program-btn');
      if (!btn) return;
      const id = btn.getAttribute('data-program-id');
      fetch(`/admin/programs/${id}`, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
          if (!data.success) throw new Error('Load failed');
          const p = data.program;
          document.getElementById('editProgramId').value = p.id;
          document.getElementById('editProgramCode').value = p.code || '';
          document.getElementById('editProgramName').value = p.name || '';
          const dept = document.getElementById('editProgramDepartment');
          if (dept) dept.value = p.department_id || '';
          clearValidationErrors(editForm);
          editProgramModal?.show();
        })
        .catch(() => showToast('error', 'Failed to load program'));
    });

    // Update
    if (editForm && editProgramModal) {
      editForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearValidationErrors(editForm);
        const id = document.getElementById('editProgramId').value;
        const submitBtn = editForm.querySelector('button[type="submit"]');
        const originalHTML = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...';
        const formData = new FormData(editForm);
        fetch(`/admin/programs/${id}`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
          body: formData,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              showToast('success', data.message || 'Program updated successfully');
              editProgramModal.hide();
              applyFilters();
            } else if (data.errors) {
              displayValidationErrors(editForm, data.errors);
            } else {
              showToast('error', data.message || 'Failed to update program');
            }
          })
          .catch(() => showToast('error', 'An unexpected error occurred'))
          .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = originalHTML; });
      });
    }

    // Delete
    let programToDelete = null;
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.delete-program-btn');
      if (!btn) return;
      programToDelete = btn.getAttribute('data-program-id');
      const name = btn.getAttribute('data-program-name');
      const nameEl = document.getElementById('deleteProgramName');
      if (nameEl) nameEl.textContent = name || '';
      deleteProgramModal?.show();
    });

    const confirmDeleteBtn = document.getElementById('confirmDeleteProgramBtn');
    confirmDeleteBtn?.addEventListener('click', function () {
      if (!programToDelete) return;
      const originalHTML = this.innerHTML;
      this.disabled = true;
      this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Deleting...';
      fetch(`/admin/programs/${programToDelete}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            showToast('success', data.message || 'Program deleted successfully');
            deleteProgramModal?.hide();
            applyFilters();
          } else {
            showToast('error', data.message || 'Failed to delete program');
          }
        })
        .catch(() => showToast('error', 'An unexpected error occurred'))
        .finally(() => { this.disabled = false; this.innerHTML = originalHTML; });
    });

    // Validation helpers
    function displayValidationErrors(form, errors) {
      Object.keys(errors || {}).forEach((field) => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
          input.classList.add('is-invalid');
          const feedback = input.nextElementSibling;
          if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = errors[field][0];
          }
        }
      });
    }

    function clearValidationErrors(form) {
      if (!form) return;
      form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback').forEach((el) => (el.textContent = ''));
    }

    addProgramModalEl?.addEventListener('hidden.bs.modal', () => { addForm?.reset(); clearValidationErrors(addForm); });
    editProgramModalEl?.addEventListener('hidden.bs.modal', () => { editForm?.reset(); clearValidationErrors(editForm); });
  });
})();
