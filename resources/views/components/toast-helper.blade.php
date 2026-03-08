@push('scripts')
<script>
    /**
     * Show a toast notification programmatically (non-flash messages)
     * @param {string} message - The message to display
     * @param {string} type - 'success', 'error', 'warning', or 'info'
     * @param {number} delay - Auto-hide delay in milliseconds (default: 4000)
     */
    window.showToast = function(message, type = 'success', delay = 4000) {
        const container = document.getElementById('globalToastContainer');
        if (!container) return;

        // Map types to Bootstrap classes
        const bgClassMap = {
            'success': 'text-bg-success',
            'error': 'text-bg-danger',
            'danger': 'text-bg-danger',
            'warning': 'text-bg-warning',
            'info': 'text-bg-info'
        };
        
        const iconMap = {
            'success': 'fa-circle-check',
            'error': 'fa-circle-xmark',
            'danger': 'fa-circle-xmark',
            'warning': 'fa-triangle-exclamation',
            'info': 'fa-info-circle'
        };

        const bgClass = bgClassMap[type] || 'text-bg-info';
        const icon = iconMap[type] || 'fa-info-circle';
        const textDarkClass = type === 'warning' ? 'text-dark' : '';
        const closeButtonClass = type === 'warning' ? '' : 'btn-close-white';

        // Create toast element
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${bgClass} border-0 shadow-sm`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body ${textDarkClass}">
                    <i class="fa-solid ${icon} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close ${closeButtonClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        // Add to container
        container.appendChild(toastEl);

        // Auto-show and hide
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: delay });
        toast.show();

        // Remove from DOM after it hides
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });

        return toast;
    };
</script>
@endpush
