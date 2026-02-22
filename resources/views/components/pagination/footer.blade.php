{{-- Unified Pagination Footer Component --}}
{{-- Provides: Left (showing results) | Center (pagination links) | Right (per-page selector) --}}
<div class="d-flex justify-content-between align-items-center flex-wrap mt-3 gap-3 pagination-footer">

    <!-- Left: Showing Results Summary -->
    <div class="text-muted small flex-shrink-0">
        Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
    </div>

    <!-- Center: Pagination Links -->
    <div class="d-flex justify-content-center flex-grow-1">
        {{ $paginator->links('pagination::bootstrap-5') }}
    </div>

    <!-- Right: Per-Page Selector -->
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <label class="small text-muted mb-0 text-nowrap">Per page:</label>
        <select class="form-select form-select-sm pagination-per-page-select"
                style="width: auto;">
            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
        </select>
    </div>

</div>

@once
<style>
.pagination-footer {
    gap: 1rem;
    padding: 0.5rem 0;
}

.pagination-footer .page-link {
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
}

.pagination-footer .page-item.active .page-link {
    background-color: #660000;
    border-color: #660000;
}

.pagination-footer .page-link:hover {
    color: #660000;
    border-color: #660000;
}

/* Responsive behavior */
@media (max-width: 992px) {
    .pagination-footer {
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.5rem;
    }

    .pagination-footer > div {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .pagination-footer > div:first-child,
    .pagination-footer > div:last-child {
        width: auto;
    }
}

@media (max-width: 576px) {
    .pagination-footer {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 1rem;
    }

    .pagination-footer > div {
        width: 100%;
        justify-content: center;
    }

    .pagination-footer .form-select-sm {
        width: auto !important;
    }

    .pagination-footer .page-link {
        padding: 0.25rem 0.375rem;
        font-size: 0.8125rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global per-page selector handler
    document.querySelectorAll('.pagination-per-page-select').forEach(select => {
        select.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    });
});
</script>
@endonce
