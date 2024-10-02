
<div class="row">
    <div class="col-md-12">
        <h4 class="col-md-12">@lang('app.paymentCredential') @lang('app.settings') <hr></h4>
    </div>
</div>
@if ($paymentCredential->paypal_status != 'active' && $paymentCredential->stripe_status != 'active' && $paymentCredential->razorpay_status != 'active')
    <div class="row alert alert-warning m-0 ml-2">
        <div class="col-md-12 d-flex align-items-center">@lang('app.superAdminPaymentGatewayMessage') </div>
    </div>
@endif

@if ($paymentCredential->paypal_status == 'active')
    <div class="row ml-2">
        <div class="col-md-6">
            <h5 class="text-info">@lang('app.paypal')</h5>
            @if (!$paypalPaymentSetting && $paymentCredential->paypal_status == 'active')
                <button id="paypal-get-started" type="button" class="btn btn-success" onclick="this.blur()" data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionDescription')">
                    <i class="fa fa-play"></i> @lang('modules.paymentCredential.getStarted')
                </button>
            @else
                <button id="" type="button" class="btn btn-info"
                    onclick="this.blur()" data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionsDescription')"
                    disabled>&nbsp;&nbsp;&nbsp;@lang('app.paypal')&nbsp;&nbsp;&nbsp;</button>
            @endif
                <div id="paypal-account-id-display" class="mt-2 form-group @if (!$paypalPaymentSetting) d-none @endif">
                @if ($paypalPaymentSetting && $paypalPaymentSetting->account_id)
                    <h5 class="text-default">
                        @lang('app.yourAccountId'):
                        <span>
                            {{ $paypalPaymentSetting->account_id }}
                        </span>
                    </h5>
                @endif
            </div>
        </div>

        <div class="col-md-3">
            <h5 class="text-info">@lang('app.status')</h5>
            <div class="form-group">
                <span id="ConnectionStatus" class="p-1 @if (!is_null($paypalPaymentSetting)
                && $paypalPaymentSetting->account_status === 'active' && $paypalPaymentSetting->connection_status === 'connected') badge badge-success @else badge badge-danger @endif">{{ $paypalPaymentSetting && $paypalPaymentSetting->account_status === 'active' && $paypalPaymentSetting->connection_status === 'connected' ? __('app.connected') : __('app.notConnected') }}</span>&nbsp;
            </div>
        </div>

    </div>

    @if ($paypalPaymentSetting && $paypalPaymentSetting->account_status === 'inactive' && $paypalPaymentSetting->connection_status === 'not_connected')
        <br>
        <div id="paypal-email-verification"
            class="row ml-2 @if($paypalPaymentSetting && $paypalPaymentSetting->account_status === 'active' && $paypalPaymentSetting->connection_status === 'connected') d-none @endif">
            <div class="col-md-12">
                <div class="d-block">
                    <h6 class="text-default mr-3">
                        @lang('app.verificationEmail')
                        <a class="mr-3" href="javascript:;" onclick="verifyEmail()">@lang('app.verificationEmailLink')</a>
                    </h6>
                </div>
            </div>
        </div>
    @endif
    <br>
    <div id="paypal-verification"
        class="{{ $paypalPaymentSetting && $paypalPaymentSetting->connection_status === 'not_connected' ? '' : 'd-none' }} row ml-2">
        <div class="col-md-12">
            <div class="d-block">
                <h5 class="text-default mr-3">
                    @lang('app.verificationLink'):
                </h5>
                <a class="mr-3" target="_blank"
                    href="{{ $paypalPaymentSetting->link ?? '' }}&displayMode=minibrowser"
                    data-paypal-button="true">
                    {{ $paypalPaymentSetting->link ?? '' }}
                </a>
            </div>
        </div>
    </div>
    <hr>
@endif
    
@if ($paymentCredential->stripe_status == 'active')
    <div class="row ml-2">
        <div class="col-md-6">
            <h5 class="text-info">@lang('app.stripe')</h5>
            @if (!$stripePaymentSetting && $paymentCredential->stripe_status == 'active')
                <button id="stripe-get-started" type="button" class="btn btn-success" onclick="this.blur()"
                    data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionDescription')">
                    <i class="fa fa-play"></i> @lang('modules.paymentCredential.getStarted')
                </button>
            @else
                <button id="stripe-connect-getStarted" type="button" class="btn btn-info" onclick="this.blur()"
                    data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionsDescription')"
                    disabled>&nbsp;&nbsp;&nbsp;@lang('app.stripe')&nbsp;&nbsp;&nbsp;</button>
            @endif
            <div id="account-id-display" class="form-group @if (!$stripePaymentSetting) d-none @endif">
                <h5 class="text-default">@lang('app.yourAccountId'):
                    <span>{{ $stripePaymentSetting->account_id ?? '' }}</span>
                </h5>
            </div>
        </div>

        <div class="col-md-3">
            <h5 class="text-info">@lang('app.status')</h5>
            <div class="form-group">
                <span
                    class="badge {{ $stripePaymentSetting && $stripePaymentSetting->connection_status === 'connected' ? 'badge-success' : 'badge-danger' }}">{{ $stripePaymentSetting && $stripePaymentSetting->connection_status === 'connected' ? __('app.connected') : __('app.notConnected') }}</span>
            </div>
        </div>
    </div>
    <br>
    <div id="stripe-verification" class="{{ $stripePaymentSetting && $stripePaymentSetting->connection_status === 'not_connected' ? '' : 'd-none' }} row ml-2">
        <div class="col-md-12">
            <div class="d-flex">
                <h5 class="text-default mr-3">
                    @lang('app.verificationLink'):
                </h5>
                <a class="mr-3" href="{{ $stripePaymentSetting->link ?? '' }}" target="_blank">
                    {{ $stripePaymentSetting->link ?? '' }}
                </a>
                @if ($stripePaymentSetting && !is_null($stripePaymentSetting->link_expire_at) && $stripePaymentSetting->link_expire_at->lessThanOrEqualTo(\Carbon\carbon::now()))
                    <button class="btn btn-info btn-sm" type="submit" value="Refresh" id="refreshLink"
                        name="refreshLink"> <i class="fa fa-refresh" aria-hidden="true"></i></button>
                @endif
            </div>
            <div id="linkExpireNote" class="form-text text-muted">
                @lang('app.linkExpireNote'):

                @if ($stripePaymentSetting && !is_null($stripePaymentSetting->link_expire_at))
                    <span>
                        {{ $stripePaymentSetting ? $stripePaymentSetting->link_expire_at->diffForHumans() : '' }}
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif

@if ($paymentCredential->razorpay_status == 'active' && $paymentCredential->stripe_status == 'active')
    <hr>
@endif

@if ($paymentCredential->razorpay_status == 'active')
    <div class="row ml-2">
        <div class="col-md-6">
            <h5 class="text-info">@lang('app.razorpay')</h5>
            @if (!$razoypayPaymentSetting && $paymentCredential->razorpay_status == 'active')
                <button id="razorpay-get-started" type="button" class="btn btn-success" onclick="this.blur()"
                    data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionDescription')">
                    <i class="fa fa-play"></i> @lang('modules.paymentCredential.getStarted')
                </button>
            @else
                <button id="razorpay-get-started" type="button" class="btn btn-info"
                    onclick="this.blur()" data-toggle="tooltip" data-original-title="@lang('modules.paymentCredential.connectionsDescription')"
                    disabled>@lang('app.razorpay')</button>
            @endif
            <div id="razor-account-id-display" class="form-group @if (!$razoypayPaymentSetting) d-none @endif">
                <h5 class="text-default">@lang('app.yourAccountId'):
                    <span>{{ $razoypayPaymentSetting->account_id ?? '' }}</span>
                </h5>
            </div>
        </div>
        <div id="razor-status" class="col-md-3">
            <h5 class="text-info">@lang('app.status')</h5>
            <div class="form-group">
                <span
                    class="badge {{ $razoypayPaymentSetting && $razoypayPaymentSetting->connection_status === 'connected' ? 'badge-success' : 'badge-danger' }}">{{ $razoypayPaymentSetting && $razoypayPaymentSetting->connection_status === 'connected' ? __('app.connected') : __('app.notConnected') }}</span>
            </div>
        </div>
    </div>
@endif
