@php($collection = $programs ?? null)
@if ($collection)
    Showing {{ $collection->firstItem() ?? 0 }} to {{ $collection->lastItem() ?? 0 }} of {{ $collection->total() }}
    programs
@else
    Showing 0 to 0 of 0 programs
@endif
