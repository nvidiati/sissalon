<div class="col-md-12 b-t permission-section d-none" id="booking-schedule-{{ $serviceLocation->locations->id }}" >
    <div class="table-responsive">
        <table class="table table-condensed">
            <tr>
                <th>#</th>
                <th>@lang('app.day')</th>
                <th>@lang('modules.settings.openTime')</th>
                <th>@lang('modules.settings.closeTime')</th>
                <th>@lang('modules.settings.allowBooking')</th>
                <th class="text-right">@lang('app.action')</th>
            </tr>
            @foreach($bookingTimes as $key=>$bookingTime)

                @if($bookingTime->location_id == $serviceLocation->location_id)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>@lang('app.'.$bookingTime->day)</td>
                    <td>{{ $serviceLocation->locations->timezone->zone_name ?? false ? $bookingTime->utc_start_time->setTimezone($serviceLocation->locations->timezone->zone_name)->format($settings->time_format) : $bookingTime->start_time }}</td>
                    <td>{{ $serviceLocation->locations->timezone->zone_name ?? false ? $bookingTime->utc_end_time->setTimezone($serviceLocation->locations->timezone->zone_name)->format($settings->time_format) : $bookingTime->end_time }}</td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" class="time-status"
                                data-row-id="{{ $bookingTime->id }}"
                                @if($bookingTime->status == 'enabled') checked @endif
                            >
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td class="text-right">
                        <a href="javascript:;" data-row-id="{{ $bookingTime->id }}"
                        class="btn btn-primary btn-rounded btn-sm edit-row"><i
                                class="icon-pencil"></i> @lang('app.edit')</a>
                    </td>
                </tr>
                @endif
            @endforeach
        </table>
    </div>
</div>

