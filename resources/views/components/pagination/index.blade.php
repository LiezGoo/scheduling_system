{{-- Global Reusable Pagination Component --}}
<!-- Pagination Results Summary -->
<div class="text-muted small">
    Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of
    {{ $paginator->total() }} results
</div>

<!-- Pagination Links -->
<div>
    {{ $paginator->links('pagination::bootstrap-5') }}
</div>
