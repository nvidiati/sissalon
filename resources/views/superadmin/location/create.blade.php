@extends('layouts.master')
@push('head-css')
<style>
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 37px;
}
.select2-container .select2-selection--single {
    height: calc(2.875rem + 2px);
}

.required-span {
    color:red;
}

.googlemap {
    height: 400px;
}
</style>
@endpush
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">@lang('app.add') @lang('app.location')</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form role="form" id="createForm" class="ajax-form" method="POST"
                        onkeydown="return event.key != 'Enter';">
                        @csrf

                        <input type="hidden" name="redirect_url" value="{{ url()->previous() }}">

                        <div class="row">
                            <div class="col-md-6">
                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.location') @lang('app.name') <span class="required-span">*</span></label>
                                    <input type="text" class="form-control form-control-lg" name="name" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('app.location') @lang('app.country') <span class="required-span">*</span></label>
                                    <div class="input-group form-group">
                                        <select name="country_id" id="country_id" class="form-control select2">
                                            <option value="">@lang('app.select') @lang('app.location')</option>
                                            @foreach($countries as $country)
                                            <option value="{{$country->id}}">{{'+'.$country->phonecode.' - '.$country->name}}</option>
                                            @endforeach
                                        </select>
                                     </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('app.latitude') <span class="required-span">*</span></label>
                                    <input type="text" id="latitude" class="form-control form-control-lg" name="lat" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('app.longitude') <span class="required-span">*</span></label>
                                    <input type="text" id="longitude"  class="form-control form-control-lg" name="lng" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('app.location') @lang('app.timezone') <span class="required-span">*</span></label>
                                    <div class="input-group form-group">
                                        <select name="timezone_id" id="timezone_id" class="form-control select2">
                                            <option value="">@lang('app.select') @lang('app.timezone')</option>
                                        </select>
                                     </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_name" class="control-label">@lang('app.company') @lang('app.location')</label>
                                    <input type="text" class="form-control form-control-lg" id="location">
                                </div>
                            </div>
                            <div class="col-md-12 p-3">
                                <div class="googlemap"></div>
                                <label class="control-label text-danger">@lang("app.superAdminAllowMapDevMessage")</label>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="button" id="save-form" class="btn btn-success btn-light-round"><i
                                            class="fa fa-check"></i> @lang('app.save')</button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection

@push('footer-js')

    @if (!empty($googleMapAPIKey))
        <script type="text/javascript" src='https://maps.google.com/maps/api/js?key={{$googleMapAPIKey->map_key}}&sensor=false&libraries=places&language={{app()->getLocale()}}'></script>
        <script src="{{ asset('js/locationpicker.jquery.js') }}"></script>
            <script>

                    $('.googlemap').locationpicker({
                        location: {
                            latitude: 0,
                            longitude: 0
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

        </script>
    @endif

    <script>
        $('body').on('click', '#save-form', function() {
            $.easyAjax({
                url: '{{ route('superadmin.locations.store') }}',
                container: '#createForm',
                type: "POST",
                redirect: true,
                file: true,
                data: $('#createForm').serialize()
            })
        });

        $('#country_id').change(function () {
            $.easyAjax({
                url: '{{ route('superadmin.timezone') }}',
                type: "GET",
                redirect: false,
                data: {"_token": "{{ csrf_token() }}", 'country_id' : this.value},
                dataType: "JSON",
                success: function (response){
                    let option = '';
                    $('#timezone_id').html(`<option value=''>@lang('app.select') @lang('app.timezone')</option>`);
                    response.forEach(timezone => {
                        option = `<option value='${timezone.id}'>${timezone.zone_name}</option>`;
                        $('#timezone_id').append(option);
                    });
                }
            })
        });

    </script>

@endpush
