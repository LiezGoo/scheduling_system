@props(['title', 'subtitle' => null, 'badge' => null])

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">{{ $title }}</h5>
                @if ($subtitle)
                    <p class="text-muted small mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($badge)
                <span class="badge bg-light text-dark">{{ $badge }}</span>
            @endif
        </div>
        {{ $slot }}
    </div>
</div>
