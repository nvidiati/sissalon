@foreach($serviceLocations as $serviceLocation)
        <div class="col-md-12 b-all mt-2">
            <div class="row bg-dark p-3 justify-content-center align-items-center">
                <div class="col-md-4">
                    <h5 class="text-white mt-2 mb-2"><strong>{{ ucwords($serviceLocation->name) }}</strong></h5>
                </div>
                <div class="col-md-4 booking-schedule-color">
                    <a href="javascript:;" class="btn btn-default text-dark btn-sm btn-rounded pull-right" onclick="toggle('#employee-schedule-{{ $serviceLocation->id }}')" data-booking-time-id="{{ $serviceLocation->id }}"><i class="fa fa-pencil"></i> @lang('app.edit') @lang('app.schedule')</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 b-t permission-section d-none" id="employee-schedule-{{ $serviceLocation->id }}" >
                    <div class="table-responsive" id="abcd">
                        <table class="table">
                            <thead class="thead-light">
                                <tr>
                                    <th>@lang('app.day')</th>
                                    <th>@lang('app.isworking')</th>
                                    <th>@lang('app.StartTime')</th>
                                    <th>@lang('app.endTime')</th>
                                    <th class="text-right">@lang('app.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedules as $schedule)
                                    @if($schedule->location_id == $serviceLocation->id)
                                        <tr>
                                            <td>{{ ucWords($schedule->days) }}</td>
                                            <td>
                                                <label class="switch" title="Permission required !!">
                                                    <input type="checkbox" name="isWorking" class="update-working"
                                                        id="isWorking{{ $schedule->id }}" data-id="{{ $schedule->id }}"
                                                        data-empid="{{ $schedule->employee_id }}" value="yes" @if ($schedule->is_working == 'yes') checked @endif
                                                        @if (!auth()->user()->roles()->withoutGlobalScopes()->first()->hasPermission('update_employee_schedule'))
                                                    disabled
                                    @endif>
                                    <span class="slider round"></span>
                                    </label>
                                    </td>
                                    <td>
                                        <div class="timePicker" id="startinputId{{ $schedule->id }}">
                                            <span id="startTime-{{ $schedule->id }}">
                                                {{ $schedule->is_working == 'yes' ? $schedule->location_start_time->format($settings->time_format) : '-------' }}
                                            </span>
                                        </div>
                                        <input type="hidden" id="hiddenstarttime{{ $schedule->id }}"
                                            value="{{ $schedule->location_start_time->format($settings->time_format) }}">
                                    </td>
                                    <td>
                                        <div class="timePicker" id="endinputId{{ $schedule->id }}">
                                            <span id="endTime-{{ $schedule->id }}">
                                                {{ $schedule->is_working == 'yes' ? $schedule->location_end_time->format($settings->time_format) : '-------' }}
                                            </span>
                                        </div>
                                        <input type="hidden" id="hiddenendtime{{ $schedule->id }}"
                                            value="{{ $schedule->location_end_time->format($settings->time_format) }}">
                                    </td>

                                    <td id="editButton{{ $schedule->id }}">
                                        @if ($schedule->is_working == 'yes')
                                            <a href="javascript:;" title="Permission required !!" data-id="{{ $schedule->id }}"
                                                data-empid="{{ $schedule->employee_id }}" class="btn btn-primary btn-circle edit-details">
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    </td>
                                    </tr>
                                @endif
                                <input type="hidden" name="schedule_startTime" id="schedule_startTime-{{ $schedule->id }}"
                                    value="{{ $schedule->location_start_time->format('h:i a') }}">
                                <input type="hidden" name="schedule_endTime" id="schedule_endTime-{{ $schedule->id }}"
                                    value="{{ $schedule->location_end_time->format('h:i a') }}">
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
