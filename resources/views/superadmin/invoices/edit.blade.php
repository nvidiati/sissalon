<div class="modal-header">
    <h5>@lang('menu.updateOfflineCommission')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <section class="mt-3 mb-3">
        <form class="form-horizontal ajax-form" id="editOfflineCommissionForm" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.company') @lang('app.name')</h6>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name="company_name" id="company_name" value="{{ $offlineCommission->company->company_name }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.totalEarning')</h6>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name="total_earning" id="total_earning" value="{{ $offlineCommission->total_amount }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.total') @lang('app.commissionAmount')</h6>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name="total_commission" id="total_commission" value="{{ $offlineCommission->commission_amount }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.pendingAmount')</h6>
                    <div class="form-group">
                        <input type="hidden" name="pending_commission" id="pending_commission" value="{{ $offlineCommission->pending_amount }}">
                        <input type="text" class="form-control form-control-lg" name="pending_amount" id="pending_amount" value="{{ $offlineCommission->pending_amount }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.paidAmount')</h6>
                    <div class="form-group">
                        <input type="number" id="paid_amount" class="form-control form-control-lg" @if (!empty($offlineCommission)) value="{{ $offlineCommission->deposit_amount }}" @endif name="paid_amount" min="0" step="any">
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-primary">@lang('app.paidOn')</h6>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name="paid_on" id="paid_on" value="{{  \Carbon\Carbon::parse($offlineCommission->paid_on)->format($settings->date_format.' '.$settings->time_format) }}" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="">@lang('app.status')</label>
                        <select name="status" id="status" class="form-control form-control-lg">
                        <option @if($offlineCommission->status == 'pending') selected @endif
                        value="pending">@lang('app.pending')</option>
                        <option @if($offlineCommission->status == 'settled') selected @endif
                        value="settled">@lang('app.settled')</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i>
        @lang('app.cancel')</button>
    <button type="button" id="updateofflineCommission" data-row-id="{{ $offlineCommission->id }}" class="btn btn-success btn-light-round"><i
        class="fa fa-check"></i> @lang('app.submit')</button>
</div>

<script>
    let startDate = '{{  \Carbon\Carbon::parse($offlineCommission->paid_on)->format("Y-m-d") }}';

    $('#paid_on').datetimepicker({
        format: '{{ $date_picker_format }}',
        locale: '{{ $settings->locale }}',
        allowInputToggle: true,
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down",
            previous: "fa fa-angle-double-left",
            next: "fa fa-angle-double-right"
        }
    }).on('dp.change', function(e) {
        $('#paid_on').val(moment(e.date).format('YYYY-MM-DD'));
    });

    $('body').on('click', '#updateofflineCommission', function() {
        $.easyAjax({
            url: '{{route('superadmin.invoices.update', $offlineCommission->id)}}',
            container: '#editOfflineCommissionForm',
            type: "POST",
            redirect: true,
            file:true,
            success: function (response) {
                if(response.status == 'success'){
                    window.location.reload();
                }
            }
        })
    });

    $('body').on('keyup', '#paid_amount', function() {
        let cashGiven = $(this).val();
        if(cashGiven === ''){
            cashGiven = 0;
        }

        let total = $('#total_commission').val();
        let cashRemaining = (parseFloat(total).toFixed(2) - parseFloat(cashGiven)).toFixed(2);

        if(cashRemaining < 0){
            cashRemaining = parseFloat(parseFloat(0).toFixed(2), 2);
        }

        $('#pending_amount').val(cashRemaining);
        $('#pending_commission').val(cashRemaining);
    });

</script>
@include("partials.backend.currency_format")
