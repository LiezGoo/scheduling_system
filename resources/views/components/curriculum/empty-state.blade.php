@props(['icon' => 'fa-solid fa-graduation-cap', 'title', 'subtitle'])

<div class="text-center py-5">
    <div class="empty-state-icon mb-3">
        <i class="{{ $icon }}"></i>
    </div>
    <h5 class="fw-semibold mb-2">{{ $title }}</h5>
    <p class="text-muted mb-0">{{ $subtitle }}</p>
</div>
