<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white border-0" style="background-color: #8B0000;">
                <h5 class="modal-title text-white fw-semibold">
                    <i id="confirmIcon" class="fas fa-exclamation-triangle me-2"></i><span id="confirmTitle">Confirmation Required</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" class="mb-0">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" id="confirmCancelBtn" data-bs-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="button" class="btn btn-maroon fw-semibold" id="confirmBtn" aria-label="Confirm Action" style="background-color: #8B0000; border-color: #8B0000;">
                    <i id="confirmBtnIcon" class="fas me-2"></i><span id="confirmBtnText">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #confirmBtn {
        background-color: #8B0000 !important;
        border-color: #8B0000 !important;
        color: white;
    }

    #confirmBtn:hover {
        background-color: #5A0000 !important;
        border-color: #5A0000 !important;
        color: white;
    }

    .confirm-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        flex: 1;
    }
</style>
@endpush
