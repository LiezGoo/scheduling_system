@props([
    'id',
    'title',
    'action' => 'javascript:void(0)',
    'method' => 'POST',
    'confirmLabel' => 'Confirm',
    'confirmClass' => 'btn-maroon',
    'confirmId' => null,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white">
                <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="{{ $confirmId ?? $id . 'Form' }}" method="POST" action="{{ $action }}">
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <div class="modal-body">
                    {{ $slot }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn {{ $confirmClass }}" id="{{ $confirmId ?? $id . 'Confirm' }}">
                        {{ $confirmLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
