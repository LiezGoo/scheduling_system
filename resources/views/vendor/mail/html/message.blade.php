@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header')
            [{{ config('app.name') }}]({{ url('/') }})
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ now()->year }} [{{ config('app.name') }}]({{ url('/') }}). @lang('All rights reserved.')
        @endcomponent
    @endslot
@endcomponent
