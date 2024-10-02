<div class="modal-header">
    <h4 class="modal-title">@lang('app.pay')</h4>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <div class="form-body">
        <div class="row">
            <div class="col-md-12 ">
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-2 h5">@lang('app.total'):</div>
                        <div class="col-md-8 h5" id="payment-modal-total">
                            {{$amount}}
                        </div>
                        <input type="hidden" id="remaining" value="{{$amountPending ?? false ? $amountPending : '0' }}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" checked type="radio" name="payment_gateway" id="pay-cash"
                            value="cash">
                        <label class="form-check-label" for="pay-cash">@lang('modules.booking.payViaCash')</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payment_gateway" id="pay-card" value="card">
                        <label class="form-check-label" for="pay-card">@lang('modules.booking.payViaCard')</label>
                    </div>
                </div>
                <div id="cash-mode">
                    <div class="form-group">
                        <label for="">@lang('modules.booking.amountGivenByCustomer')</label>
                        <input type="number" min="0" step=".01" class="form-control form-control-lg" id="cash-given" value="{{ (isset($serviceType) && $serviceType === 'online') ? str_replace($currencySymbol, '', $amount) : '' }}" {{ (isset($serviceType) && $serviceType === 'online') ? 'disabled' : ''}}>
                        <div id="user-error" class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="">@lang('modules.booking.cashRemaining')</label>
                            <input type='hidden' name='pending_amount' id="pending-amount" value="{{$amountPending ?? false ? $amountPending : '-' }}" >
                            <div class="col-md-12 h5" id="cash-remaining">{{$amountPending ?? false ? $amountPending : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i>
        @lang('app.cancel')</button>
    <button type="button" id="submit-cart" class="btn btn-success"><i class="fa fa-check"></i>
        @lang('app.submit')</button>
</div>
