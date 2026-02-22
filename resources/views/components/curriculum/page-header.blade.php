@props(['title', 'subtitle'])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div class="curriculum-header">
        <div class="border-start border-3 ps-3" style="border-color: #660000;">
            <h1 class="h4 fw-bold mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>
    @if (isset($actions))
        <div class="d-flex align-items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
