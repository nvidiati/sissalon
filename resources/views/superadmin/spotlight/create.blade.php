@extends('layouts.master')

@push('head-css')
    <style>
        .select2-container--default .select2-selection--single{height: 31px !important;}
        .select2-container--default .select2-selection--single .select2-selection__rendered{line-height: 2.4 !important;}
        .select2 {
            width: 100% !important;
        }

        .required-span {
            color:red;
        }
        
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-dark">
                <div class="card-header">
                    <div class="card-header">
                    <h3 class="card-title">@lang('menu.spotlight')</h3>
                    </div>
                </div>
                <div class="modal-body">
                    <form role="form" id="createSpotlightForm" class="ajax-form" method="POST" autocomplete="off" onkey>
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group" id="emp">
                                <label>@lang('app.company')<span class="required-span">*</span></label>
                                <select name="company" class="form-control-lg select2 company">
                                <option value="" >@lang('app.select') @lang('app.company')</option>
                                @foreach($company as $companies)
                                <option value="{{$companies->id}}">{{$companies->company_name}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group" id="emp">
                                <label>@lang('app.deal')<span class="required-span">*</span></label>
                                <select name="deal" id="deal" class="form-control form-control-lg select2">
                                <option value="" >@lang('app.select') @lang('app.deal')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="">@lang('report.fromDate')<span class="required-span">*</span></label>
                            <div class="input-group form-group">
                                <input type="text" class="form-control" name="fromdate" id="fromdate" value="">
                                <span class="input-group-append input-group-addon">
                                <button type="button" class="btn btn-info" disabled><span class="fa fa-calendar-o"></span></button>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="">@lang('report.toDate')<span class="required-span">*</span></label>
                            <div class="input-group form-group">
                                <input type="text" class="form-control" name="todate" id="todate" value="">
                                <span class="input-group-append input-group-addon">
                                <button type="button" class="btn btn-info" disabled><span class="fa fa-calendar-o"></span></button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" id="save-form" class="btn btn-success btn-light-round"><i
                            class="fa fa-check"></i> @lang('app.save')</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-js')
<script>
    var fromDate = '';
    var toDate = '';

    $('body').on('click', '#save-form', function() {
        const form = $('#createSpotlightForm');
        var company = $('select[name="company"]').val();
        var deal = $('select[name="deal"]').val();

        $.easyAjax({
            url: '{{route('superadmin.spotlight-deal.store')}}',
            container: '#createSpotlightForm',
            type: "POST",
            redirect: true,
            data: form.serialize()+'&fromDate='+fromDate+'&toDate='+toDate,
            success: function (response) {
                if(response.status == 'success'){
                    window.location.href = '{{ route('superadmin.spotlight-deal.index') }}'
                }
            }
        })
    });

    $('body').on('change', '.company', function() {
        var id = $(this).val();
        if(id) {
            var url = "{{ route('superadmin.getdeal', ":id") }}";
            url = url.replace(':id', id);
                jQuery.ajax({
                    url : url,
                    type : "GET",
                    dataType : "json",
                    success:function(data)
                    {
                        jQuery('select[name="deal"]').empty();
                        jQuery.each(data, function(key,value){
                            $('select[name="deal"]').append('<option value="'+ key +'">'+ value +'</option>');
                        });
                    }
                });
        } else {
            $('select[name="deal"]').empty();
        }
    });

    $('#fromdate').datetimepicker({
            format: '{{ $date_picker_format }}',
            locale: '{{ $settings->locale }}',
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-angle-double-left",
                next: "fa fa-angle-double-right"
            },
            useCurrent: false,
        }).on('dp.change', function(e) {
            fromDate =  moment(e.date).format('YYYY-MM-DD');
        });

        $('#todate').datetimepicker({
            format: '{{ $date_picker_format }}',
            locale: '{{ $settings->locale }}',
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down",
                previous: "fa fa-angle-double-left",
                next: "fa fa-angle-double-right"
            },
            useCurrent: false,
        }).on('dp.change', function(e) {
            toDate =  moment(e.date).format('YYYY-MM-DD');
        });

        function convert(str) {
            var date = new Date(str);
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'pm' : 'am';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            minutes = minutes < 10 ? '0'+minutes : minutes;
            hours = ("0" + hours).slice(-2);
            var strTime = hours + ':' + minutes + ' ' + ampm;
            return strTime;
        }

</script>
@endpush
