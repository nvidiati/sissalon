<ul class="list-items f-14 pl-0 mb-0 mt-3">
    @foreach ($bookingTimes as $bookingTime)
        <li class="d-flex justify-content-between mb-1">
            {{ __('app.'.$bookingTime->day) }}
            <span class="">
                {{ $bookingTime->status == 'enabled' ? strtoupper($bookingTime->start_time) . ' - ' . strtoupper($bookingTime->end_time) : __('app.close') }}
            </span>
        </li>
    @endforeach
</ul>