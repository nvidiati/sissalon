<style>
    .select2-container--default .select2-selection--single {
        height: 31px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 2.4 !important;
    }

    .select2 {
        width: 100% !important;
    }
</style>

<div class="modal-header" id="modal-header-div">
    <h5 class="modal-title">@lang('app.change') @lang('app.package')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>

<div class="modal-body">
    <form role="form" id="changePackageForm" class="ajax-form" method="POST" autocomplete="off" onkey>
        @csrf
        <div class="row">
            <input type="hidden" name="companyId" id="companyId" value="{{ $company->id }}">
            <div class="col-md-12">
                <label for="companyNAme">@lang('app.company') @lang('app.name')</label>
                <div class="input-group form-group">
                    <input type="text" class="form-control" name="companyNAme" id="companyNAme"
                        value="{{ $company->company_name }}" value disabled="disabled">
                </div>
            </div>
            <div class="packageIdDiv @if($company->package->type == 'trial') col-md-6 @else col-md-12 @endif">
                <div class="form-group" id="pack">
                    <label>@lang('app.package')</label>
                    <select name="packageId" id="packageId" class="form-control">
                        @foreach ($allPackages as $Packages)
                        <option value="{{ $Packages->id }}" @if ($Packages->id == $company->package->id) selected @endif
                            data-monthly-price='{{ $Packages->monthly_price }}'
                            data-annual-price='{{ $Packages->annual_price }}'>{{ ucfirst($Packages->name) }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6 packageDateDiv @if($company->package->type == 'trial') d-none @endif">
                <label for="packageType">@lang('app.package') @lang('app.type')</label>
                <div class="input-group form-group">
                    <select name="packageType" id="packageType" class="form-control">
                        <option value="monthly">@lang('app.monthly')</option>
                        <option value="annual" selected>@lang('app.annual')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 packageDateDiv @if($company->package->type == 'trial') d-none @endif">
                <label for="amount">@lang('app.amount')</label>
                <div class="input-group form-group">
                    <input onkeypress="return isNumberKey(event)" type="number" class="form-control" name="amount"
                        id="amount" min="0" value="{{ $company->package->annual_price }}">
                </div>
            </div>
            <div class="col-md-6 packageDateDiv @if($company->package->type == 'trial') d-none @endif">
                <label for="payDate">@lang('app.pay') @lang('app.date')</label>
                <div class="input-group form-group">
                    <input type="text" class="form-control" name="payDate" id="payDate"
                        value="{{ \Carbon\Carbon::now()->format($settings->date_format) }}">
                    <button type="button" class="btn btn-info" disabled><span class="fa fa-calendar-o"></span></button>
                </div>
            </div>
            <div class="col-md-6">
                <label for="licenceExpireDate">@lang('app.licence') @lang('app.expires') @lang('app.on')</label>
                <div class="input-group form-group">
                    <input type="text" class="form-control" name="licenceExpireDate" id="licenceExpireDate"
                        @if(!is_null($company->trial_ends_at)) value="{{
                    \Carbon\Carbon::parse($company->trial_ends_at)->format($settings->date_format) }}"
                    @elseif($company->licence_expire_on) value="{{
                    \Carbon\Carbon::parse($company->licence_expire_on)->format($settings->date_format) }}" 
                    @else
                        value="{{ \Carbon\Carbon::now()->addYear('1')->format($settings->date_format) }}"
                    @endif
                    readonly>
                </div>
            </div>
            <div class="col-md-12">
                <label for="paymentMethod">@lang('app.choose') @lang('app.payment') @lang('app.method') <span>
                        <a href="{{ route('superadmin.payment-settings.index') }}#offline"
                            class="btn btn-success">@lang('app.addNew')</a>
                    </span></label>
                <div class="input-group form-group">
                    <select name="paymentMethod" id="paymentMethod" class="form-control select2">
                        @foreach ($methods as $method)
                        <option value="{{ $method->id }}">{{ strtoupper($method->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i>
        @lang('app.cancel')</button>
    <button type="button" class="btn btn-success" onclick="updatePackage()"><i class="fa fa-pencil"></i>
        @lang('app.submit')</button>
</div>


<script>

    $('#packageId').on('change', function() {
        
        if (this.value == 1 || this.value == 2) {
            $('.packageDateDiv').addClass('d-none')

            if ($(".packageIdDiv").hasClass("col-md-12")) {
                $('.packageIdDiv').removeClass('col-md-12')
            }
            
            $('.packageIdDiv').addClass('col-md-6')
        }
        else
        {
            if ($(".packageDateDiv").hasClass("d-none")) {
                $('.packageDateDiv').removeClass('d-none')
            }

            if ($(".packageIdDiv").hasClass("col-md-12")) {
                $('.packageIdDiv').removeClass('col-md-6')
            }
            $('.packageIdDiv').addClass('col-md-12')
        }
    });

    function updatePackage() {
        const form = $('#changePackageForm');

        $.easyAjax({
            url: '{{ route('superadmin.changePackage') }}',
            container: '#changePackageForm',
            type: "POST",
            redirect: true,
            data: form.serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    setTimeout(function() {
                        window.location.href = '{{ route('superadmin.companies.index') }}'
                    }, 2000);
                }
            }
        })
    };

    $('#packageId').select2({
        dropdownParent: $('#myModal')
    }).on('change', function() {
        const addValue = $('#packageType').val() === 'monthly' ? 'monthly-price' : 'annual-price'

        $('#amount').val($('#packageId option:selected').attr('data-' + addValue));
    })

    $('#packageType').select2({
        dropdownParent: $('#myModal')
    }).on('change', function() {
        const payDate = $('#payDate').val()
        const addValue = $('#packageType').val() === 'monthly' ? 'month' : 'year'
        const addAmountValue = $('#packageType').val() === 'monthly' ? 'monthly-price' : 'annual-price'

        $('#amount').val($('#packageId option:selected').attr('data-' + addAmountValue));
        $('#licenceExpireDate').val(moment(payDate).add(1, addValue).format('{{ $date_picker_format }}'));
    })

    $('#payDate').datetimepicker({
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
        const addValue = $('#packageType').val() === 'monthly' ? 'month' : 'year'
        $('#licenceExpireDate').val(moment(e.date).add(1, addValue).format('{{ $date_picker_format }}'))
    });

    function convert(str) {
        var date = new Date(str);
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes;
        hours = ("0" + hours).slice(-2);
        var strTime = hours + ':' + minutes + ' ' + ampm;
        return strTime;
    }

    function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode
        if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;
        return true;
    }

</script>
