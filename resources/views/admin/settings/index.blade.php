@extends('layouts.master')

@push('head-css')
    <link rel="stylesheet" href="{{ asset('css/bootstrap-tagsinput.css') }}">
    <style>
        .dropify-wrapper,
        .dropify-preview,
        .dropify-render img {
            background-color: var(--sidebar-bg) !important;
        }

        #carousel-image-gallery .card .img-holder {
            height: 150px;
            overflow: hidden;
        }

        #carousel-image-gallery .card .img-holder img {
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        .note-group-select-from-files {
            display: none;
        }

        .select2-container .select2-selection--single {
            height: 39px;
        }

        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #d2d1d1;
            border-radius: 4px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #444;
            line-height: 37px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 26px;
            position: absolute;
            top: 5px;
            right: 1px;
            width: 20px;
        }
        .select2-container {
            width: 100% !important;
        }
        #account-id-display {
            margin-top: 3%;
        }

        #razor-account-id-display {
            margin-top: 3%;
        }

        .switch {
            margin-top: .2em;
        }


        .d-none {
            display: none;
        }

        .googlemap {
            height: 400px;
        }

        .bootstrap-tagsinput {
            width: 100%;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            padding: 2px 5px;
            border-radius: 2px;
        }

        .booking-schedule-color a
        {
            color: #000 !important;
        }

        .required-span {
            color:red;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12 col-md-2 mb-4 mt-3 mb-md-0 mt-md-0">
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings') && !\Session::get('loginRole'))
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#profile_page') active @endif" href="#profile_page" data-toggle="tab">@lang('menu.profile') @lang('menu.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#general') active @endif" href="#general" data-toggle="tab" id="general-tab">@lang('menu.general') @lang('app.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#vendor_page') active @endif" href="#vendor_page" data-toggle="tab">@lang('menu.vendorPage') @lang('menu.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#times') active @endif" href="#times" data-toggle="tab">@lang('menu.bookingSettings')</a>
                    @if(in_array('Zoom Meeting', $user->modules))
                        <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#zoom') active @endif" href="#zoom" data-toggle="tab">@lang('app.zoom') @lang('menu.settings')</a>
                    @endif
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#currency') active @endif" href="#currency" data-toggle="tab">@lang('app.currencyConversionRate')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#employee-schedule') active @endif" href="#employee-schedule" data-toggle="tab">@lang('app.employee') @lang('app.schedule') @lang('menu.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#admin-theme') active @endif" href="#admin-theme" data-toggle="tab">@lang('menu.adminThemeSettings')</a>
                    <a class="nav-link module-setting" href="javascript:;">@lang('menu.module') @lang('menu.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#role-permission') active @endif" href="#role-permission" data-toggle="tab">@lang('menu.rolesPermissions')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#payment') active @endif" href="#payment" data-toggle="tab">@lang('app.paymentCredential') @lang('menu.settings')</a>
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#office-leaves') active @endif" href="#office-leaves" data-toggle="tab">@lang('menu.officeleaves')</a>
                    @if (in_array('Google Calendar',$user->modules))
                        <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#googleCalendar') active @endif" href="#googleCalendar" data-toggle="tab">@lang('menu.googleCalendar')</a>
                    @endif
                @else
                    <a class="nav-link @if(Route::currentRouteName() == 'admin.settings.index#profile_page' || Route::currentRouteName() == 'admin.settings.index') active @endif" href="#profile_page" data-toggle="tab">@lang('menu.profile') @lang('menu.settings')</a>
                @endif
            </div>
        </div>
        <div class="col-12 col-md-10">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="tab-content">
                                @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings') && !\Session::get('loginRole'))

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#general') active @endif" id="general">

                                        <form class="form-horizontal ajax-form" id="general-form" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row">
                                                <h4 class="col-md-12">@lang('menu.general') @lang('app.settings') <hr></h4>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.company') @lang('app.name')</label>

                                                        <input type="text" class="form-control  form-control-lg"
                                                            id="company_name" name="company_name"
                                                            value="{{ $settings->company_name }}">
                                                    </div>

                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.company') @lang('app.email')</label>
                                                        <input type="text" class="form-control  form-control-lg"
                                                            id="company_email" name="company_email"
                                                            value="{{ $settings->company_email }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.company') @lang('app.phone')</label>
                                                        <input type="text" class="form-control  form-control-lg"
                                                            id="company_phone" name="company_phone"
                                                            value="{{ $settings->company_phone }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="exampleInputPassword1">@lang('app.logo')</label>
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <input type="file" id="input-file-now" name="logo"
                                                                    accept=".png,.jpg,.jpeg" class="dropify"
                                                                    data-default-file="{{ $settings->logo_url }}"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="exampleInputPassword1">@lang('app.address')</label>
                                                        <textarea class="form-control form-control-lg" name="address" id=""
                                                                cols="30" rows="5">{!! $settings->address !!}</textarea>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="date_format" class="control-label">
                                                                    @lang('app.date_format')
                                                                </label>

                                                                <select name="date_format" id="date_format"
                                                                        class="form-control form-control-lg select2">
                                                                    @foreach($dateFormats as $key => $dateFormat)
                                                                        <option value="{{ $key }}" @if($settings->date_format == $key) selected @endif>{{
                                                                            $key.' ('.$dateObject->format($key).')' }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="time_format" class="control-label">
                                                                    @lang('app.time_format')
                                                                </label>

                                                                <select name="time_format" id="time_format"
                                                                        class="form-control form-control-lg select2">
                                                                    @foreach($timeFormats as $key => $timeFormat)
                                                                        <option value="{{ $key }}" @if($settings->time_format == $key) selected @endif>{{
                                                                            $key.' ('.$dateObject->format($key).')' }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.company') @lang('app.website')</label>
                                                        <input type="text" class="form-control form-control-lg" id="website"
                                                            name="website" value="{{ $settings->website }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="timezone"
                                                            class="control-label w-100">@lang('app.timezone')</label>
                                                        <select name="timezone" id="timezone"
                                                                class="form-control form-control-lg select2">
                                                            @foreach($timezones as $tz)
                                                                <option @if($settings->timezone == $tz) selected @endif>{{
                                                                    $tz }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.currency')</label>
                                                        <select name="currency_id" id="currency_id"
                                                                class="form-control  form-control-lg">
                                                            @foreach($currencies as $currency)
                                                                <option
                                                                    @if($currency->id == $settings->currency_id) selected
                                                                    @endif
                                                                    value="{{ $currency->id }}">{{ $currency->currency_symbol.' ('.$currency->currency_code.')' }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="control-label">@lang('app.language')</label>
                                                        <select name="locale" id="locale"
                                                                class="form-control form-control-lg">
                                                            @forelse($enabledLanguages as $language)
                                                                <option value="{{ $language->language_code }}"
                                                                        @if($settings->locale == $language->language_code) selected @endif >
                                                                    {{ $language->language_name }}
                                                                </option>
                                                            @empty
                                                                <option @if($settings->locale == "en") selected
                                                                        @endif value="en">English
                                                                </option>
                                                            @endforelse
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button id="save-general" type="button" class="btn btn-success"><i
                                                                class="fa fa-check"></i> @lang('app.save')</button>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                    </div>
                                    {{-- CURRENCY TAB --}}
                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#currency') active @endif" id="currency">
                                        <h4 class="col-md-12">@lang('app.currencyConversionRate')</h4>
                                        <div class="row">
                                            <div class="col-md-12 table-responsive">
                                                <table class="table table-condensed">
                                                    <thead>
                                                        <tr>
                                                        <th>#</th>
                                                        <th>@lang('app.currency') @lang('app.name')</th>
                                                        <th>@lang('app.currencySymbol')</th>
                                                        <th>@lang('app.currencyCode')</th>
                                                        <th>@lang('app.exchangeRate')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($currencies as $key=>$currency)
                                                        <tr id="currency-{{ $currency->id }}">
                                                            <td>{{ ($key+1) }}</td>
                                                            <td>{{ ucwords($currency->currency_name) }}</td>
                                                            <td>{{ $currency->currency_symbol }}</td>
                                                            <td>{{ $currency->currency_code }}</td>
                                                            <td>{{ $currency->exchange_rate }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- CURRENCY TAB --}}

                                    {{-- ZOOM TAB --}}
                                    <div class="tab-pane @if(Route::currentRouteName() == 'superadmin.settings.index#zoom') active @endif" id="zoom">
                                        <h4>@lang('app.zoom') @lang('menu.settings')<hr></h4>
                                        <form class="form-horizontal ajax-form" id="zoom-form" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                        <label for="enableZoom" class="control-label">@lang('modules.enableZoom')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input type="checkbox" id="enableZoom" name="enable_zoom" value="active" @if(!is_null($zoomSetting) && $zoomSetting->enable_zoom == 'active') checked @endif>
                                                            <span class="slider round"></span>
                                                        </label>
                                                </div>

                                                <div class="@if(!is_null($zoomSetting) && $zoomSetting->enable_zoom != 'active') d-none @endif" id="zoom_option">
                                                    <div class="form-group">
                                                        <label
                                                        class="control-label">@lang("modules.zoomKey") <span class="required-span">*</span></label>
                                                        <input type="text" name="zoom_api_key" id="zoom_api_key"
                                                        class="form-control form-control-lg"
                                                        value="{{ $zoomSetting ?? false ? $zoomSetting->api_key : '' }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label
                                                        class="control-label">@lang("modules.zoomSecret") <span class="required-span">*</span></label>
                                                        <input type="text" name="zoom_secret_key" id="zoom_secret_key"
                                                        class="form-control form-control-lg"
                                                        value="{{ $zoomSetting ?? false ? $zoomSetting->secret_key : '' }}">
                                                    </div>

                                                    <div class="form-group">
                                                        <label>@lang("modules.openInZoom")?</label>
                                                        <div class="form-group">
                                                        <label class="radio-inline"> <input type="radio" class="checkbox get-driver"
                                                        value="zoom_app"
                                                        @if(!is_null($zoomSetting) && $zoomSetting->meeting_app == 'zoom_app') checked
                                                        @endif
                                                        name="meeting_app"> @lang("app.yes")</label>
                                                        <label class="radio-inline pl-lg-2"> <input type="radio" class="checkbox get-driver"
                                                        value="in_app"
                                                        @if(!is_null($zoomSetting) && $zoomSetting->meeting_app == 'in_app') checked
                                                        @endif
                                                        name="meeting_app"> @lang("app.no")</label>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <p class="text-primary"> @lang('app.webHookUrl'):-  {{ route('zoom-webhook') }}<br><span class='text-danger'>(@lang('messages.webHookUrl'))</span></p>
                                                    </div>

                                                    <div class="form-group">
                                                        <button id="save-zoom-setting" type="button" class="btn btn-success"><i
                                                        class="fa fa-check"></i> @lang('app.save')</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--/span-->
                                        </div>
                                        </form>
                                    </div>

                                    {{-- Zoom tab ends --}}

                                    <!-- /.tab-pane -->
                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#vendor_page') active @endif" id="vendor_page">
                                        @include('admin.vendor-page.index')
                                    </div>

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#times') active @endif" id="times">
                                        <form id="booking-times-form" method="post" onkeydown="return event.key != 'Enter';">
                                            @csrf
                                            <div class="row">
                                                <h4 class="col-md-12">@lang('menu.bookingSettings')<hr></h4>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.multiTaskingEmployee')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('app.assignMultipleEmployeeAtSameTimeSlot')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input type="checkbox" name="multi_task_user" id="multi_task_user" value="enabled" @if ( $settings->multi_task_user=='enabled') checked @endif onchange="multiTaskingEmpChanged()">
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.limit') @lang('app.booking')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('app.maxBookingPerCustomer')</label>
                                                        <br>
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                            <input onkeypress="return isNumberKey(event)" class="form-control" type="number" name="no_of_booking_per_customer" min="0" value="{{$settings->booking_per_day}}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.allowEmployeeSelection')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('messages.allowEmployeeSelectionMSG')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input value="enabled" type="checkbox" name="employee_selection" @if ( $settings->employee_selection=='enabled') checked @endif>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.disableSlotDurationAsPerServiceDuration')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('app.disableSlotDurationMSG')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input @if ( $settings->disable_slot=='enabled') checked @endif value="enabled" type="checkbox" name="disable_slot" id="disable_slot" onchange="disableSlotChanged()">
                                                            <span class="slider round"></span>
                                                        </label>

                                                        <div class="row @if($settings->disable_slot=='disabled' || $settings->disable_slot=='') d-none @endif" id="div_disable_slot">
                                                            <br>
                                                            <div class="col-md-8">
                                                            <label class="radio-inline pl-lg-2"><input type="radio" @if($settings->booking_time_type == 'sum') checked @endif
                                                                value="sum" name="booking_time_type" class="booking_time_type"> @lang('app.sum')</label>
                                                            <label class="radio-inline pl-lg-2"><input type="radio"
                                                                @if($settings->booking_time_type == 'avg') checked @endif
                                                                value="avg" name="booking_time_type" class="booking_time_type"> @lang('app.average')</label>
                                                            <label class="radio-inline pl-lg-2"><input type="radio"
                                                                @if($settings->booking_time_type == 'max') checked @endif
                                                                value="max" name="booking_time_type" class="booking_time_type"> @lang('app.maximum')</label>
                                                            <label class="radio-inline pl-lg-2"><input type="radio"
                                                                @if($settings->booking_time_type == 'min') checked @endif
                                                                value="min" name="booking_time_type" class="booking_time_type"> @lang('app.minimum')</label>
                                                            </div>
                                                            <div class="col-12 alert alert-info" role="alert" id="info-msg">

                                                                @if($settings->booking_time_type == 'sum') @lang('messages.sumOfServiceTime').
                                                                @endif
                                                                @if($settings->booking_time_type == 'max') @lang('messages.MaxServiceTime').
                                                                @endif
                                                                @if($settings->booking_time_type == 'min') @lang('messages.MinServiceTime').
                                                                @endif
                                                                @if($settings->booking_time_type == 'avg')@lang('messages.AvgOfServiceTime').form-control-sm
                                                                @endif

                                                            </div>
                                                            <div class="col-12 alert alert-warning" role="alert">
                                                                @lang('messages.disablePaymentsFromFront').
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.cronjob')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('app.cronjobtitle')</label>
                                                        <br>
                                                        <label class="switch" style="margin-top: .2em">
                                                            <input  @if($settings->cron_status == 'active')
                                                            checked
                                                        @endif  value='active' type="checkbox" name="cron_status" id="cron_status" onchange="disableCronJobChanged()" >
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                    <div  class="col-md-12 p-0 @if($settings->cron_status=='deactive' || $settings->cron_status=='') d-none @endif" id="cron_job_from">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label for="duration" class="control-label">@lang("app.duration")</label>
                                                                    <input type="number" class="form-control" name="duration" min="1" value="{{$settings->duration}}">
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label for="duration_type" class="control-label">@lang("app.durationType")</label>
                                                                    <select name="duration_type" class="form-control">
                                                                        <option value="minutes"
                                                                            {{ $settings->duration_type == 'minutes' ? 'selected' : '' }}>
                                                                            @lang("app.minutes")
                                                                        </option>
                                                                        <option value="hours" {{ $settings->duration_type == 'hours' ? 'selected' : '' }}>
                                                                            @lang("app.hours")
                                                                        </option>
                                                                        <option value="days" {{ $settings->duration_type == 'days' ? 'selected' : '' }}>
                                                                            @lang("app.days")
                                                                        </option>
                                                                        <option value="weeks" {{ $settings->duration_type == 'weeks' ? 'selected' : '' }}>
                                                                            @lang("app.weeks")
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('app.displayDeals')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('messages.allowCompanyListDealBeforeTimeMSG')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input value="active" type="checkbox" name="display_deal" @if ( $settings->display_deal=='active') checked @endif>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('modules.booking.autoAppoveOnline')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('messages.autoApproveOnlineBooking')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input value="active" type="checkbox" name="approve_online" @if ( $settings->approve_online_booking=='active') checked @endif>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5 class="text-primary">@lang('modules.booking.autoAppoveOffline')</h5>
                                                    <div class="form-group">
                                                        <label class="control-label">@lang('messages.autoApproveOfflineBooking')</label>
                                                        <br>
                                                        <label class="switch">
                                                            <input value="active" type="checkbox" name="approve_offline" @if ( $settings->approve_offline_booking=='active') checked @endif>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <button type="button" class="btn btn-success" id="save-booking-times-field"><i
                                                        class="fa fa-check"></i>@lang('app.save')</button>
                                                </div>

                                            </div>
                                            <hr><br>

                                            <div class="row">
                                                <div class="col-md">
                                                    <h4>@lang('app.booking') @lang('app.schedule')</h4><br>
                                                    @foreach($serviceLocations as $serviceLocation)
                                                        @if(array_key_exists($serviceLocation->location_id, $serviceAtLocation))
                                                        <div class="col-md-12 b-all mt-2">
                                                            <div class="row bg-dark p-3">
                                                                <div class="col-md-6">
                                                                    <h5 class="text-white mt-2 mb-2"><strong>{{ ucwords($serviceLocation->locations->name) }}</strong></h5>
                                                                </div>
                                                                <div class="col-md-6 text-center booking-schedule-color">
                                                                    <a href="javascript:;" class="btn btn-default text-dark btn-sm btn-rounded" onclick="toggle('#booking-schedule-{{ $serviceLocation->locations->id }}')" data-booking-time-id="{{ $serviceLocation->locations->id }}"><i class="fa fa-pencil"></i> @lang('app.editBookingTime')</a>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                @include('admin.booking-time.index')
                                                            </div>
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- /.tab-pane -->

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#employee-schedule') active @endif" id="employee-schedule">
                                        @include('admin.employee-schedule.index')
                                    </div>
                                    <!-- /.tab-pane -->

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#admin-theme') active @endif" id="admin-theme">
                                        <h4 class="col-md-12">@lang('menu.adminThemeSettings')<hr></h4>
                                        <section class="mt-3 mb-3 ml-2">
                                            <form class="form-horizontal ajax-form" id="theme-form" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="row">
                                                    <h6 class="col-md-12">@lang('modules.theme.subheadings.colorPallette') <span type="button" id="resetAdminColor" class="btn badge badge-primary">@lang("app.reset")</span></h6>
                                                    <div class="col-md-2 ">
                                                        <div class="form-group">
                                                            <label>@lang('modules.theme.primaryColor')</label>
                                                            <input id="adminPrimaryColor" type="text" class="form-control color-picker"
                                                                name="primary_color"
                                                                value="{{ $adminThemeSetting->primary_color }}">
                                                            <div
                                                                style="background-color: {{ $adminThemeSetting->primary_color }}"
                                                                class=" border border-light">&nbsp;
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-2 ">
                                                        <div class="form-group">
                                                            <label>@lang('modules.theme.secondaryColor')</label>
                                                            <input id="adminSecondaryColor" type="text" class="form-control color-picker"
                                                                name="secondary_color"
                                                                value="{{ $adminThemeSetting->secondary_color }}">
                                                            <div
                                                                style="background-color: {{ $adminThemeSetting->secondary_color }}"
                                                                class=" border border-light">&nbsp;
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-3 ">
                                                        <div class="form-group">
                                                            <label>@lang('modules.theme.sidebarBgColor')</label>
                                                            <input id="adminSidebarBgColor" type="text" class="form-control color-picker"
                                                                name="sidebar_bg_color"
                                                                value="{{ $adminThemeSetting->sidebar_bg_color }}">
                                                            <div
                                                                style="background-color: {{ $adminThemeSetting->sidebar_bg_color }}"
                                                                class=" border border-1">&nbsp;
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-2 ">
                                                        <div class="form-group">
                                                            <label>@lang('modules.theme.sidebarTextColor')</label>
                                                            <input id="adminSidebarTextColor" type="text" class="form-control color-picker"
                                                                name="sidebar_text_color"
                                                                value="{{ $adminThemeSetting->sidebar_text_color }}">
                                                            <div
                                                                style="background-color: {{ $adminThemeSetting->sidebar_text_color }}"
                                                                class="border border-light">&nbsp;
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-2 ">
                                                        <div class="form-group">
                                                            <label>@lang('modules.theme.topbarTextColor')</label>
                                                            <input id="adminTopbarTextColor" type="text" class="form-control color-picker"
                                                                name="topbar_text_color"
                                                                value="{{ $adminThemeSetting->topbar_text_color }}">
                                                            <div
                                                                style="background-color: {{ $adminThemeSetting->topbar_text_color }}"
                                                                class="border border-1">&nbsp;
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>

                                                <div class="row mb-3">
                                                    <h6 class="col-md-12">@lang('modules.theme.subheadings.customCss') <span type="button" id="resetAdminCustomCss" class="btn badge badge-primary">@lang("app.reset")</span></h6>

                                                    <div class="col-md-12">
                                                        <div id="admin-custom-css">@if(!$adminThemeSetting->custom_css)@lang('modules.theme.defaultCssMessage')@else{!! $adminThemeSetting->custom_css !!}@endif</div>
                                                    </div>

                                                    <input id="admin-custom-input" type="hidden" name="admin_custom_css">
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button id="save-theme" type="button" class="btn btn-success"><i
                                                                class="fa fa-check"></i> @lang('app.save')</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </section>
                                    </div>
                                    <!-- /.tab-pane -->

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#role-permission') active @endif" id="role-permission">
                                        @include('admin.role-permission.index')
                                    </div>

                                    <!-- /.tab-pane -->
                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#office-leaves') active @endif" id="office-leaves">
                                        @include('admin.office-leaves.index')
                                    </div>

                                    @if (in_array('Google Calendar',$user->modules))
                                        <!-- /.tab-pane -->
                                        <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#googleCalendar') active @endif" id="googleCalendar">
                                            @include('admin.google-calendar.index')
                                        </div>
                                    @endif

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#payment') active @endif" id="payment">
                                        @include('admin.payment-settings.index')
                                    </div>
                                    <!-- /.tab-pane -->

                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#profile_page' || Route::currentRouteName() == 'admin.settings.index') active @endif" id="profile_page">
                                        @include('admin.profile.index')
                                    </div>

                                @else
                                    <div class="tab-pane @if(Route::currentRouteName() == 'admin.settings.index#profile_page' || Route::currentRouteName() == 'admin.settings.index') active @endif" id="profile_page">
                                        @include('admin.profile.index')
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-content -->
                </div><!-- /.card-body -->
            </div>
            <!-- /.nav-tabs-custom -->
        </div>
    </div>
@endsection

@push('footer-js')
    <script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
    <script src="{{ asset('/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js') }}"></script>
    <script src="{{ asset('assets/ace/ace.js') }}" type="text/javascript" charset="utf-8"></script>
    <script src="{{ asset('js/bootstrap-tagsinput.js') }}"></script>
    @if ($superadmin->map_option == 'active' && $user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings'))
    <script type="text/javascript" src='https://maps.google.com/maps/api/js?key={{$superadmin->map_key}}&sensor=false&libraries=places&language={{app()->getLocale()}}'></script>
    <script src="{{ asset('js/locationpicker.jquery.js') }}"></script>
    @endif
    <script>
        $(function () {
            $('body').on('click', '#v-pills-tab a', function(e) {
                e.preventDefault();
                $(this).tab('show');
                $("html, body").scrollTop(0);
            });

            $(document).ready(function(){
                $(window).scrollTop(0);
            });

            $('.wrong-currency-message').hide();

            // store the currently selected tab in the hash value
            $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
                var id = $(e.target).attr("href").substr(1);
                window.location.hash = id;
            });

            // on load of the page: switch to the currently selected tab
            var hash = window.location.hash;
            $('#v-pills-tab a[href="' + hash + '"]').tab('show');
        });
    </script>
    @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings'))
        <script>

            var adminCssEditor = ace.edit('admin-custom-css', {
                mode: 'ace/mode/css',
                theme: 'ace/theme/twilight'
            });

            function checkCurrencyCode(currency_code) {
                if ( currency_code === 'INR') {
                    return true;
                }
                else {
                    return false;
                }
            }

            function verifyEmail() {
                let url = "{{ route('admin.paypal.verifyMerchantDetails') }}";
                let token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'GET',
                    url: url,
                    data: {'_token': token},
                    success: function (response) {
                        if (response.status == "success") {
                            $.unblockUI();
                            $('#paypal-email-verification').addClass('d-none');
                            $('#ConnectionStatus').html("__('app.connected')");
                            $('#ConnectionStatus').removeClass('badge badge-danger');
                            $('#ConnectionStatus').addClass('badge badge-success');
                        }
                    }
                });
            }

            // Employee-schedule table
            employeeScheduleTable = $('#employeeScheduleTable').dataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: '{!! route('admin.employee-schedule.index') !!}',

                "fnDrawCallback": function( oSettings ) {
                    $("body").tooltip({
                        selector: '[data-toggle="tooltip"]'
                    });
                },
                order: [[1, 'ASC']],
                columns: [
                    { data: 'DT_RowIndex'},
                    { data: 'name', name: 'name' },
                    { data: 'action', name: 'action', width: '20%' }
                ]
            });
            new $.fn.dataTable.FixedHeader( employeeScheduleTable );


            $('body').on('click', '.view-employee-detail', function () {
                var id = $(this).data('row-id');
                var url = '{{ route('admin.employee-schedule.show', ':id')}}';
                url = url.replace(':id', id);

                $(modal_lg + ' ' + modal_heading).html('@lang('app.view') @lang('app.schedule')');
                $.ajaxModal(modal_lg, url);

            });

            $('body').on('click', '.edit-row', function () {
                var id = $(this).data('row-id');
                var url = '{{ route('admin.booking-times.edit', ':id')}}';
                url = url.replace(':id', id);

                $(modal_lg + ' ' + modal_heading).html('@lang('app.edit') @lang('app.bookingTimes')');
                $.ajaxModal(modal_lg, url);
            });

            $('.edit-officeLeave').click(function () {
                var id = $(this).data('row-id');
                var url = '{{ route('admin.office-leaves.edit', ':id')}}';
                url = url.replace(':id', id);

                $(modal_default + ' ' + modal_heading).html('@lang('app.edit') @lang('app.officeleaves')');
                $.ajaxModal(modal_default, url);
            });

            $('body').on('click', '#create-office-leaves', function () {
                    var url = '{{ route('admin.office-leaves.create') }}';

                $(modal_default + ' ' + modal_heading).html('@lang('app.createNew') @lang('app.officeleaves')');
                $.ajaxModal(modal_default, url);
            });

            $('body').on('click', '.delete-officeLeave', function(){
                var id = $(this).data('row-id');
                swal({
                    icon: "warning",
                    buttons: ["@lang('app.cancel')", "@lang('app.ok')"],
                    dangerMode: true,
                    title: "@lang('errors.areYouSure')",
                    text: "@lang('errors.deleteWarning')",
                }).then((willDelete) => {
                    if (willDelete) {
                        var url = "{{ route('admin.office-leaves.destroy',':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {'_token': token, '_method': 'DELETE'},
                            success: function (response) {
                                if (response.status == "success") {
                                    $.unblockUI();
                                    // swal("Deleted!", response.message, "success");
                                    location.reload();
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('change', '#enableZoom', function() {
                if ($(this).is(':checked')) {
                    $('#zoom_option').removeClass('d-none')
                } else {
                    $('#zoom_option').addClass('d-none')
                }
            });

            $('body').on('click', '#save-zoom-setting', function() {
                $.easyAjax({
                    url: '{{route('admin.zoom-settings.update', $zoomSetting ?? false ? $zoomSetting->id : '')}}',
                    container: '#zoom-form',
                    type: "POST",
                    data: $('#zoom-form').serialize(),
                    success: function (response) {
                        if (response.status == 'success') {
                            location.reload();
                        }
                    }
                });
            });

            $('.dropify').dropify({
                messages: {
                    default: '@lang("app.dragDrop")',
                    replace: '@lang("app.dragDropReplace")',
                    remove: '@lang("app.remove")',
                    error: '@lang('app.largeFile')'
                }
            });

            $('.color-picker').colorpicker({
                format: 'hex'
            }).on('change', function (e) {
                $(this).siblings('div').css('background-color', e.value)
            });

            $('.time-status').change(function () {
                var id = $(this).data('row-id');
                var url = "{{route('admin.booking-times.update', ':id')}}";
                url = url.replace(':id', id);

                if ($(this).is(':checked')) {
                    var status = 'enabled';
                } else {
                    var status = 'disabled';
                }

                $.easyAjax({
                    url: url,
                    type: "POST",
                    data: {'_method': 'PUT', '_token': "{{ csrf_token() }}", 'status': status}
                })
            });

            $('body').on('click', '#save-category', function () {
                var id = $(this).data('row-id');
                var url = "{{route('admin.booking-times.update', ':id')}}";
                url = url.replace(':id', id);
                $.easyAjax({
                    url: url,
                    container: '#createProjectCategory',
                    type: "POST",
                    data: $('#createProjectCategory').serialize(),
                    success: function (response) {
                        if(response.status == 'success'){
                            window.location.reload();
                        }
                    }
                })
            });

            $('.offline-payment').change(function () {
                if ($(this).is(':checked')) {
                    $('#offlinePayment').val(1);
                } else {
                    $('#offlinePayment').val(0);
                }
            });

            function toggle(elementBox) {
                $(elementBox).toggleClass('d-none', 1000);
            }

            function toggleRazorPay(elementBox) {
                var elBox = $(elementBox);
                if (checkCurrencyCode('{{ $settings->currency->currency_code }}')) {
                    elBox.slideToggle();
                    $('.wrong-currency-message').fadeOut();
                }
                else {
                    $('.wrong-currency-message').fadeIn();
                    $('#razorpay_status').prop('checked', false);
                }
            }

            $('body').on('click', '#save-general', function () {
                $.easyAjax({
                    url: '{{route('admin.settings.update', $settings->id)}}',
                    container: '#general-form',
                    type: "POST",
                    file: true
                })
            });

            $('body').on('click', '#paypal-get-started', function() {
                $.easyAjax({
                    url: "{{ route('admin.paypal.createPaypalAccountLink') }}",
                    type: 'GET',
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#paypal-get-started').addClass('d-none')
                            $('#paypal-verification').removeClass('d-none')
                            $('#paypal-verification').find('a').html(response.action_url).attr('href', `${response.action_url}&displayMode=minibrowser`)
                        }
                    }
                })
            });

            $('body').on('click', '#stripe-get-started', function() {
                $.easyAjax({
                    url: "{{ route('front.createAccountLink') }}",
                    type: 'GET',
                    success: function (response) {
                        if (response.status === 'success') {
                            $('#stripe-get-started').addClass('d-none')
                            $('#account-id-display').removeClass('d-none')
                            $('#account-id-display').find('span').html(response.details.account_id)

                            $('#stripe-verification').removeClass('d-none')
                            $('#stripe-connect-getStarted').removeClass('d-none')
                            $('#stripe-verification').find('a').html(response.details.link).attr('href', response.details.link)
                            $('#stripe-verification').find('span').html(response.link_expire_at)
                        }
                    }
                })
            })

            $('body').on('click', '#refreshLink', function () {
                $.easyAjax({
                    url: '{{route('admin.refreshLink', $stripePaymentSetting->id ?? '')}}',
                    type: "GET",
                    success: function (response) {
                        if (response.status == 'success') {
                            location.reload();
                        }
                    }
                })
            });

            $('body').on('click', '#razorpay-get-started', function() {
                $(modal_default + ' ' + modal_heading).html('---');
                $.ajaxModal(modal_default, "{{route('admin.credential.accountLinkForm')}}");
            })

            $('body').on('click', '#save-theme', function () {
                $('#admin-custom-input').val(adminCssEditor.getValue());
                $.easyAjax({
                    url: '{{route('admin.theme-settings.update', $adminThemeSetting->id)}}',
                    container: '#theme-form',
                    type: "POST",
                    data: $('#theme-form').serialize(),
                    success: function (response) {
                        if (response.status == 'success') {
                            location.reload();
                        }
                    }
                })
            });

            $('body').on('click', '#save-booking-times-field', function () {
                $.easyAjax({
                    url: '{{route('admin.save-booking-times-field')}}',
                    container: '#booking-times-form',
                    type: "POST",
                    data: $('#booking-times-form').serialize(),
                    success: function (response) {
                        if (response.status == 'success') {
                            location.reload();
                        }
                        if (response.status == 'error') {
                            location.reload();
                        }
                    }

                })
            });

            function isNumberKey(evt) {
                var charCode = (evt.which) ? evt.which : evt.keyCode
                if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
                return true;
            }

            function disableSlotChanged() {
                if($('#disable_slot').is(":checked")) {
                    $("#div_disable_slot").removeClass('d-none');
                    $('#multi_task_user').prop("checked", false);
                } else {
                    $("#div_disable_slot").addClass('d-none');
                }
            }
            function disableCronJobChanged() {
                if($('#cron_status').is(":checked")) {
                    $("#cron_job_from").removeClass('d-none');

                } else {
                    $("#cron_job_from").addClass('d-none');
                }
            }

            function multiTaskingEmpChanged() {
                if($('#multi_task_user').is(":checked")) {
                    $("#div_disable_slot").addClass('d-none');
                    $('#disable_slot').prop("checked", false);
                }
            }

            $("body").on('click', '.booking_time_type', function(){
                let duration_type = '';
                if($(this).val()=='sum'){
                    duration_type = "@lang('messages.sumOfServiceTime').";
                }
                else if($(this).val()=='avg'){
                    duration_type = "@lang('messages.AvgOfServiceTime').";
                }
                else if($(this).val()=='max'){
                    duration_type = "@lang('messages.MaxServiceTime').";
                }
                else if($(this).val()=='min'){
                    duration_type = "@lang('messages.MinServiceTime').";
                }
                $('#info-msg').html(duration_type+'..!');
            });

            $('body').on('click', '.module-setting', function() {
                var url = "{{ route('admin.moduleSetting') }}#admin-setting";
                window.location.href = url;
            });
            $('#description').summernote({
                dialogsInBody: true,
                height: 300,
                toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough']],
                ['fontsize', ['fontsize']],
                ['para', ['ul', 'ol', 'paragraph']],
                ["view", ["fullscreen"]]
            ]
            })


            var mockFile = {!! $vendorPage->images !!};
            var defaultImage = '';
            var lastIndex = 0;
            Dropzone.autoDiscover = false;
            //Dropzone class
            myDropzone = new Dropzone("#file-upload-dropzone", {
                url: "{{ route('admin.vendor-page.updateImages') }}",
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                paramName: "file",
                maxFilesize: 10,
                maxFiles: 10,
                acceptedFiles: "image/*",
                autoProcessQueue: false,
                uploadMultiple: true,
                addRemoveLinks:true,
                parallelUploads:10,
                init: function () {
                    myDropzone = this;
                },
                dictDefaultMessage: "@lang('app.dropzone.defaultMessage')",
                dictRemoveFile: "@lang('app.dropzone.removeFile')"
            });

            myDropzone.on('sending', function(file, xhr, formData) {
                formData.append('vendor_page_id', {{$vendorPage->id}});
                if (mockFile.length > 0) {
                    formData.append('uploaded_files', JSON.stringify(mockFile));
                }
                formData.append('default_image', defaultImage);
            });

            myDropzone.on('addedfile', function (file) {
                var index = mockFile.findIndex(x => x.name == file.name);
                if (index === -1) {
                    index = lastIndex + 1;
                }
                lastIndex = index;

                var div = document.createElement('div');
                div.className = 'form-check form-check-inline';
                var input = document.createElement('input');
                input.className = 'form-check-input';
                input.type = 'radio';
                input.name = 'default_image';
                input.id = 'default-image-'+index;
                input.value = file.name;
                if ('{{ $vendorPage->default_image }}' == file.name) {
                    input.checked = true;
                }
                div.appendChild(input);
                var label = document.createElement('label');
                label.className = 'form-check-label';
                label.innerHTML = "@lang('app.dropzone.makeDefaultImage')";
                label.htmlFor = 'default-image-'+index;
                div.appendChild(label);
                file.previewTemplate.appendChild(div);

            })

            myDropzone.on('removedfile', function (file) {
                var index = mockFile.findIndex(x => x.name == file.name);
                mockFile.splice(index, 1);

                var token = "{{ csrf_token() }}";
                var vendorId = "{{ $vendorPage->id }}";

                defaultImage = $('input[name=default_image]:checked').val();

                $.easyAjax({
                    url: '{{route('admin.vendor-page.deleteImage', $vendorPage->id)}}',
                    type: "POST",
                    data: {'_token': token, '_method': 'POST', 'vendorId' : vendorId, 'file' : file.name, 'default_image' : defaultImage},
                })
            })

            myDropzone.on('removedfile', function (file) {
                var index = mockFile.findIndex(x => x.name == file.name);
                mockFile.splice(index, 1);
            })

            // Create the mock file:
            mockFile.forEach(file => {
                var path = "{{ asset_url('vendor-page/'.$vendorPage->id.'/:file_name') }}";
                path = path.replace(':file_name', file.name);
                myDropzone.emit('addedfile', file);
                myDropzone.emit('thumbnail', file, path);
                myDropzone.files.push(file);
                myDropzone.emit("complete", file);
            });

            myDropzone.options.maxFiles = myDropzone.options.maxFiles - mockFile.length;
            myDropzone.on("maxfilesexceeded", function(file) { this.removeFile(file); });

            $('body').on('click', '#saveVendorPage', function () {
                $.easyAjax({
                    url: '{{route('admin.update-vendor-page',$vendorPage->id)}}',
                    container: '#vendorPageForm',
                    type: "POST",
                    file:true,
                    data: $('#vendorPageForm').serialize(),
                    success: function (response) {
                        defaultImage = response.defaultImage;
                        if (myDropzone.getQueuedFiles().length > 0) {
                            myDropzone.processQueue();
                        }
                        else{
                            if (myDropzone.defaultOptions) {
                                var blob = new Blob();
                                blob.upload = { 'chunked': myDropzone.defaultOptions.chunking };
                                myDropzone.uploadFile(blob);
                            }
                        }
                        var msgs = "@lang('messages.updatedSuccessfully')";
                        $.showToastr(msgs, 'success');
                        window.location.href = '{{ route('admin.settings.index') }}#vendor_page';                  }
                })
            });
            @if ($superadmin->map_option == 'active')
            $('.googlemap').locationpicker({
                location: {
                    latitude: {{ $vendorPage->latitude?$vendorPage->latitude:'26.85259403535702' }},
                    longitude: {{ $vendorPage->longitude?$vendorPage->longitude:'75.80531537532806' }}
                },
                radius: 0,
                zoom: 4,
                inputBinding: {
                    latitudeInput: $('#latitude'),
                    longitudeInput: $('#longitude'),
                    locationNameInput: $('#location')
                },
                enableAutocomplete: true

            });
            @endif
            $('body').on('change', '#map_option', function() {
                    if ($(this).is(':checked')) {
                        $('#map_key_option').removeClass('d-none')
                    } else {
                        $('#map_key_option').addClass('d-none')
                    }
                });


                $('body').on('change', '#currency_id', function () {
                    var currency_id = {{$settings->currency_id}};
                    var selectedVal = $("#currency_id").val();
                    if (currency_id != selectedVal) {
                        swal({
                            icon: "warning",
                            buttons: ["@lang('app.cancel')", "@lang('app.ok')"],
                            dangerMode: true,
                            title: "@lang('errors.areYouSure')",
                            text: "@lang('errors.changeCurrency')",
                        })
                        .then((willDelete) => {
                            if (!willDelete) {
                                $("#currency_id").val(currency_id).change();
                            }
                        });
                    }

                });

            // Change Colors using Reset Button
            function colorChange(element,value) {
                element.val(value);
                element.siblings('div').css('background-color', value);
            }

            $('body').on('click', '#resetAdminColor', function() {
                colorChange($('#adminPrimaryColor'),'#414552');
                colorChange($('#adminSecondaryColor'),'#788AE2');
                colorChange($('#adminSidebarBgColor'),'#FFFFFF');
                colorChange($('#adminSidebarTextColor'),'#5C5C62');
                colorChange($('#adminTopbarTextColor'),'#FFFFFF');
            });

            $('body').on('click', '#resetAdminCustomCss', function() {
                    adminCssEditor.setValue('@lang('modules.theme.defaultCssMessage')');
            });

            @if (auth()->user()->googleAccount)
                $('body').on('click', '#googleCalendarDisconnect', function(){
                    var id = $(this).data('row-id');
                    swal({
                        icon: "warning",
                        buttons: ["@lang('app.cancel')", "@lang('app.ok')"],
                        dangerMode: true,
                        title: "@lang('errors.areYouSure')",
                        text: "@lang('errors.deleteWarning')",
                    })
                        .then((willDelete) => {
                            if (willDelete) {
                                var url = "{{ route('googleAuth.destroy',auth()->user()->googleAccount->id) }}";
                                var token = "{{ csrf_token() }}";

                                $.easyAjax({
                                    type: 'POST',
                                    url: url,
                                    data: {'_token': token, '_method': 'DELETE'},
                                    success: function (response) {
                                        if (response.status == "success") {
                                            $.unblockUI();
                                            location.reload();
                                        }
                                    }
                                });
                            }
                        });
                });
            @endif

            @if (!auth()->user()->is_customer)

                var fieldHTML = '<div class="row"> <div class="col-6"> <div class="form-group"> <label for="duration" class="control-label">@lang("app.duration")</label> <input type="number" class="form-control form-control-lg" name="duration[]" min="1" value="1"> </div> </div> <div class="col-5"> <label for="duration_type" class="control-label">@lang("app.durationType")</label> <select name="duration_type[]"  class="form-control form-control-lg"> <option value="minutes">@lang("app.minutes")</option> <option value="hours">@lang("app.hours")</option> <option value="days">@lang("app.days")</option> <option value="weeks">@lang("app.weeks")</option> </select> </div> <div class="col-1 pt-3"> <a href="javascript:;" class="btn btn-danger btn-sm btn-circle removeNotification mt-4" data-row-id="0"><i class="fa fa-times" aria-hidden="true"></i></a> </div> </div> '; //New input field html
                var notifactionCounter = {{$companyBookingNotification->count()}}; //Initial field counter is 1
                //Once add button is clicked
                $('body').on('click', '#addNotification', function(){
                    //Check maximum number of input fields
                    if (notifactionCounter < 2) {
                        notifactionCounter++; //Increment field counter
                        $('.field_wrapper').append(fieldHTML); //Add field html
                        $('#bookingNotificationFormBtn').removeClass('d-none');
                    }
                    if(notifactionCounter==2){
                        $('.addNotification').addClass('d-none');
                    }

                });
                //Once remove button is clicked
                $('body').on('click', '.field_wrapper .removeNotification', function(e) {
                    e.preventDefault();
                    $(this).parent('div').parent('div').remove(); //Remove field html
                    notifactionCounter--; //Decrement field counter
                    if(notifactionCounter < 2){
                        $('.addNotification').removeClass('d-none');
                    }

                    if(notifactionCounter==0){
                        $('#bookingNotificationFormBtn').addClass('d-none');
                    }

                    var id = $(this).data('row-id');
                    if (id) {
                        var url = "{{ route('admin.google.notification.destroy',':id') }}";
                        url = url.replace(':id', id);
                        var token = "{{ csrf_token() }}";
                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {'_token': token, '_method': 'DELETE'},
                        });
                    }
                });

            @endif

            $('body').on('click', '#saveBookingNotificationForm', function () {
                $.easyAjax({
                    url: '{{route('admin.google.notification.store')}}',
                    container: '#bookingNotificationForm',
                    type: "POST",
                    data: $('#bookingNotificationForm').serialize(),
                    success: function (response) {
                        if (response.status == "success") {
                            $.unblockUI();
                            location.reload();
                        }
                    }
                })
            });

        </script>
    @endif

@endpush
