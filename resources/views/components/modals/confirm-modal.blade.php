<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #660000; border: none;">
                <h5 class="modal-title text-white" style="font-weight: 600;">
                    <i id="confirmIcon" class="fas fa-exclamation-triangle me-2"></i><span id="confirmTitle">Confirmation Required</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" class="mb-0">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cancel">Cancel</button>
                <button type="button" class="btn btn-danger fw-semibold" id="confirmBtn" aria-label="Confirm Action">
                    <i id="confirmBtnIcon" class="fas me-2"></i><span id="confirmBtnText">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>
