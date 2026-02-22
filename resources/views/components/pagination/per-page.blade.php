{{-- Per-Page Selector Component --}}
{{-- Automatically handles per_page URL parameter updates --}}
<div class="d-flex align-items-center gap-2">
    <label class="small text-muted mb-0">Per page:</label>
    <select class="form-select form-select-sm pagination-per-page-select"
            style="width: auto;">
        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
    </select>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global per-page selector handler
    document.querySelectorAll('.pagination-per-page-select').forEach(select => {
        select.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1'); // Reset to first page
            window.location.href = url.toString();
        });
    });
});
</script>
@endonce
