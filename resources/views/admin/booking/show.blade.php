@extends('layouts.master')

@push('head-css')
<style>
    #users-list {
        margin: 0.2em;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">@lang('app.bookingDetail')</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 text-right mt-2 mb-2">
                        @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_booking') && $user->roles()->withoutGlobalScopes()->first()->name != 'customer' && $current_emp_role->name != 'customer' && $booking->status !== 'completed')
                        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-primary edit-booking" data-booking-id="{{ $booking->id }}" type="button"><i class="fa fa-edit"></i> @lang('app.edit')</a>
                        @endif
                        @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_booking') && $current_emp_role->name != 'customer' && $booking->payment_status != 'completed')
                        <button class="btn btn-sm btn-outline-danger delete-row" data-row-id="{{ $booking->id }}" type="button"><i class="fa fa-times"></i> @lang('app.delete') @lang('app.booking')</button>
                        @endif
                        @if ($booking->status == 'pending')
                            @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_booking') && $booking->date_time != '' && $booking->date_time->greaterThanOrEqualTo(\Carbon\Carbon::now()) && $current_emp_role->name != 'customer')
                            <a href="javascript:;" data-booking-id="{{ $booking->id }}" class="btn btn-outline-dark btn-sm send-reminder"><i class="fa fa-send"></i> @lang('modules.booking.sendReminder')</a>
                            @endif
                            @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_booking') && $current_emp_role->name === 'customer')
                            <button class="btn btn-sm btn-outline-danger cancel-row" data-row-id="{{ $booking->id }}" type="button"><i class="fa fa-times"></i> @lang('modules.booking.requestCancellation')</button>
                            @endif
                        @endif
                    </div>

                    <div class="col-md-12 text-center mb-3">
                        <img src="{{ $booking->user ? $booking->user->user_image_url : '' }}" class="border img-bordered-sm img-circle" height="70em" width="70em">
                        <h6 class="text-uppercase mt-2">{{ ucwords($booking->user ? $booking->user->name : '') }}</h6>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-6 border-right"> <strong>@lang('app.email')</strong> <br>
                        <p class="text-muted"><i class="icon-email"></i> {{ $booking->user->email ?? '--' }}</p>
                    </div>
                    <div class="col-md-6"> <strong>@lang('app.mobile')</strong> <br>
                        <p class="text-muted"><i class="icon-mobile"></i> {{ $booking->user->mobile ? $booking->user->formatted_mobile : '--' }}</p>
                    </div>
                </div>
                <hr>

                <div class="row">
                    <div class="col-sm-4 border-right"> <strong>@lang('app.booking') @lang('app.date')</strong> <br>
                        <p class="text-primary"><i class="icon-calendar"></i>
                            @if ($booking->date_time != '')
                                {{  \Carbon\Carbon::parse($booking->date_time)->translatedFormat($settings->date_format) }}
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-4 border-right"> <strong>@lang('app.booking') @lang('app.time')</strong> <br>
                        <p class="text-primary"><i class="icon-alarm-clock"></i>
                            @if ($booking->date_time != '')
                                {{ $booking->date_time->translatedFormat($settings->time_format) }}
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-4"> <strong>@lang('app.booking') @lang('app.status')</strong> <br>
                        <span class="text-uppercase small border
                        @if($booking->status == 'completed') border-success text-success @endif
                        @if($booking->status == 'pending') border-warning text-warning @endif
                        @if($booking->status == 'approved') border-info text-info @endif
                        @if($booking->status == 'in progress') border-primary text-primary @endif
                        @if($booking->status == 'canceled') border-danger text-danger @endif
                        badge-pill">{{ __('app.'.$booking->status) }}</span>
                    </div>
                </div>
                <hr>

                @if(count($booking->users)>0)
                    <div class="row">
                        <div class="col-sm-12"> <strong>@lang('menu.employee') </strong> <br>
                            <p class="text-primary" id="users-list">
                                @foreach ($booking->users as $user)
                                    &nbsp;&nbsp;&nbsp;  <i class="icon-user"></i> {{$user->name}}
                                @endforeach
                            </p>
                        </div>
                    </div>
                    <hr>
                @endif

                @if($booking->booking_type === 'online' && $booking->status === 'approved')
                    <div class="row">
                        <div class="col-sm-6 border-right"> <strong>@lang('menu.bookingType')</strong> <br>
                            <p class="text-success"><i class="icon-active"></i>
                                @lang('app.online')
                            </p>
                        </div>
                        <div class="col-sm-6 border-right text-center"> <br>
                            @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_booking') && $user->roles()->withoutGlobalScopes()->first()->name != 'customer' && $current_emp_role->name != 'customer' && $booking->users->first()->id === Auth::user()->id && $booking->status === 'approved')
                                <a target="_blank" href='{{ $meeting->start_link }}' class="btn btn-primary btn-sm">@lang('app.startMeeting')</a>
                            @elseif($booking->status === 'approved' && $booking->user->id === Auth::user()->id)
                                <a target="_blank" href='{{ $meeting->join_link }}' class="btn btn-primary btn-sm">@lang('app.joinMeeting')</a>
                            @endif
                        </div>
                    </div>
                    <hr>
                @endif
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-condensed">
                            <thead class="bg-secondary">
                            <tr>
                                <th>#</th>
                                <th>@lang('app.item')</th>
                                <th>@lang('app.unitPrice')</th>
                                <th>@lang('app.quantity')</th>
                                <th class="text-right">@lang('app.amount')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($booking->items as $key=>$item)

                                @php
                                    $item_name = '';
                                    $item_type = '';
                                    if(!is_null($item->deal_id) && is_null($item->business_service_id) && is_null($item->product_id)) {
                                        $item_name ='<a href="javascript:;" class="view-deal" data-row-id="'.$item->deal_id.'">'.ucwords($item->deal->title). '</a><br> <small class="badge-pill badge-primary " >Deal</small>';
                                        $item_type = 'deal';
                                    }
                                    else if(is_null($item->deal_id) && is_null($item->business_service_id) && !is_null($item->product_id)) {
                                        $item_name = $item->product->name.'<br> <small class="badge-pill badge-secondary " >Product</small>';
                                        $item_type = 'product';
                                    }
                                    else if(is_null($item->deal_id) && !is_null($item->business_service_id) && is_null($item->product_id)) {
                                        $item_name = ucwords($item->businessService->name).'<br> <small class="badge-pill badge-info " >Service</small>';
                                        $item_type = 'service';
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $key+1 }}.</td>
                                    <td>{!! $item_name !!} </td>
                                    <td>{{ $user->hasRole('customer')? $item->formated_unit_price : currencyFormatter(number_format((float)($item->unit_price), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    <td>x{{ $item->quantity }}</td>
                                    <td class="text-right">{{ $user->hasRole('customer')? currencyFormatter(($item->converted_unit_price) * $item->quantity) : currencyFormatter(number_format((($item->unit_price) * $item->quantity), 2, '.', ''), myCurrencySymbol())}}</td>
                                </tr>
                            @endforeach
                            </tbody>

                        </table>
                    </div>
                    <div class="col-md-7 border-top">
                        <div class="col-md-2 mt-2 mb-1">
                            @if ($user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_booking') && $user->roles()->withoutGlobalScopes()->first()->name != 'customer' && $current_emp_role->name != 'customer' && $booking->payment_status == 'pending')
                                <button class="btn btn-sm btn-outline-primary add-payment" id='add-payment' data-booking-id="{{ $booking->id }}" type="button"><i class="fa fa-plus"></i> @lang('app.addPayment')</button>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-condensed">
                                    <thead class="bg-secondary">
                                        <tr>
                                            <th>#</th>
                                            <th>@lang('app.date')</th>
                                            <th>@lang('front.paymentMethod')</th>
                                            <th>@lang('app.amount')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($booking->bookingPayments as $i=>$payment)
                                            <tr>
                                                <td>{{ ++$i }}.</td>
                                                <td>{{ $payment->paid_on }}</td>
                                                <td>{{ ucfirst($payment->gateway) }}</td>
                                                <td>{{ myCurrencySymbol().$payment->amount_paid }}</td>
                                            </tr>
                                        @endforeach
                                    @if ($commonCondition)
                                    <tr>
                                        <td colspan="2">
                                            <div class="payment-type">
                                                <h5>@lang('front.paymentMethod')</h5>
                                                <div class="payments text-center">
                                                    <div class="row col-md-12">
                                                        @if($credentials->stripe_status == 'active' && $current_emp_role->name == 'customer')
                                                            <a href="javascript:;" id="stripePaymentButton" data-bookingId="{{ $booking->id }}" class="btn btn-custom btn-blue mb-2 stripePayButton"><i class="fa fa-cc-stripe mr-2"></i>@lang('front.buttons.stripe')</a>
                                                        @endif
                                                        @if($credentials->paypal_status == 'active' && $current_emp_role->name == 'customer')
                                                            {{-- <div class="ml-2 mr-2" id="paypal-button-container"> --}}
                                                            {{-- <a href="{{ route('front.paypal', $booking->id) }}" class="btn btn-custom btn-blue mb-2"><i class="fa fa-paypal mr-2"></i>@lang('front.buttons.paypal')</a> --}}
                                                        @endif
                                                        @if($credentials->razorpay_status == 'active' && $current_emp_role->name == 'customer')
                                                            <a href="javascript:;" id="razorpayButton" class="btn btn-custom btn-blue mb-2"><i class="fa fa-card mr-2"></i>@lang('front.buttons.razorpay')</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif

                                    @if($booking->status == 'completed')
                                    <tr>
                                        <td>
                                            <a target="_blank" href="{{ route('admin.bookings.print', $booking->id) }}" class="btn btn-outline-info btn-sm"><i class="fa fa-print"></i> @lang('app.print') @lang('app.receipt')</a>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.bookings.download', $booking->id) }}" class="btn btn-outline-success btn-sm"><i class="fa fa-download"></i> @lang('app.download') @lang('app.receipt')</a>
                                        </td>
                                    </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 border-top amountDetail">
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-condensed ml-0">
                                    <tr class="h6">
                                        <td class="border-top-0 text-left">@lang('app.service') @lang('app.total')</td>
                                        <td class="border-top-0">{{ $user->hasRole('customer')? $booking->formated_original_amount : currencyFormatter(number_format((float)($booking->original_amount), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>

                                    @if ($booking->product_amount > 0)
                                    <tr class="h6">
                                        <td class="border-top-0 text-left">@lang('app.product') @lang('app.total')</td>
                                    <td class="border-top-0">{{ $user->hasRole('customer')? $booking->formated_product_amount : currencyFormatter(number_format((float)($booking->product_amount), 2, '.', ''),myCurrencySymbol()) }}</td>
                                    </tr>
                                    @endif

                                    @if($booking->discount > 0)
                                    <tr class="h6">
                                        <td class="text-left">@lang('app.discountOnService')</td>
                                        <td>{{ $user->hasRole('customer')? $booking->formated_discount : currencyFormatter(number_format((float)($booking->discount), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>
                                    @endif

                                    @if($booking->coupon_discount > 0)
                                    <tr class="h6">
                                        <td class="text-left" >@lang('app.couponDiscount') (<a href="javascript:;" class="show-coupon">{{ $booking->coupon->title}}</a>)</td>
                                        <td>{{ $user->hasRole('customer')? $booking->formated_coupon_discount : currencyFormatter(number_format((float)($booking->coupon_discount), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>
                                    @endif

                                    @if($booking->tax_amount > 0)
                                    <tr class="h6">
                                        <td class="text-left">@lang('app.totalTax')</td>
                                        <td>{{ $user->hasRole('customer')? $booking->formated_tax_amount : currencyFormatter(number_format((float)($booking->tax_amount), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>
                                    @endif

                                    <tr class="h6">
                                        <td class="text-left">@lang('app.total')</td>
                                        <td id='total-amount'>{{ $user->hasRole('customer')? $booking->formated_amount_to_pay : currencyFormatter(number_format((float)($booking->amount_to_pay), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>

                                    <tr class="h6">
                                        <td class="text-left">@lang('app.totalPaid')</td>
                                        <td>{{ $user->hasRole('customer')? $totalPaid : currencyFormatter(number_format((float)($totalPaid), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>

                                    <tr class="h6">
                                        <td class="text-left">@lang('app.totalRemaining')</td>
                                        <td id='total-remaining'>{{ $user->hasRole('customer')? $totalPending : currencyFormatter(number_format((float)($totalPending), 2, '.', ''), myCurrencySymbol()) }}</td>
                                    </tr>

                                    <tr class="h6">
                                        <td class="text-left">@lang('modules.booking.paymentStatus')</td>
                                        <td>
                                            @if($booking->payment_status == 'completed')
                                                <span class="text-success  font-weight-normal"><i class="fa fa-check-circle"></i> {{ __('app.'.$booking->payment_status) }}</span></td>
                                            @endif
                                            @if($booking->payment_status == 'pending')
                                                <span class="text-warning font-weight-normal"><i class="fa fa-times-circle"></i> {{ __('app.'.$booking->payment_status) }}</span></td>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    </div>

                    @if(!is_null($booking->additional_notes))
                    <div class="col-md-12 font-italic">
                        <h4 class="text-info">@lang('modules.booking.customerMessage')</h4>
                        <p class="text-lg">
                            {!! $booking->additional_notes !!}
                        </p>
                    </div>
                    @endif

                    <!-- Start Customer Feedback -->
                    @if ($booking->status == 'completed' && $item_type == 'service' && ($user->hasRole('customer') || $current_emp_role->name == 'customer') && $superadmin->rating_status == 'active')
                        <div class="col-md-12 ml-2 text-right">
                            <a data-bs-toggle="modal" href="javascript:;" class="d-flex align-items-center text-info" onclick="feedback();" id="feedback">
                                @if ($ratings->count() > 0)
                                    @lang('modules.booking.viewFeedback')
                                @else
                                    @lang('modules.booking.giveFeedback')
                                @endif
                                &nbsp;<i class="fa fa-arrow-right f-20 text-black mr-1"></i>
                            </a>
                        </div>
                    @endif
                    <!-- End Customer Feedback -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('footer-js')

    <script>

        $('body').on('click', '.cancel-row', function(){
            var id = $(this).data('row-id');
            swal({
                icon: "warning",
                buttons: ["@lang('app.cancel')", "@lang('app.ok')"],
                dangerMode: true,
                title: "@lang('errors.areYouSure')",
            }).then((willDelete) => {
                if (willDelete) {
                    let current_url = "?current_url="+'bookingPage';
                    var url = "{{ route('admin.bookings.requestCancel',':id') }}"+current_url;
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'POST'},
                        // success: function (response) {
                        //     if (response.status == "success") {
                        //         $.unblockUI();
                        //         $('#booking-detail').hide().html(response.view).fadeIn('slow');
                        //         location.reload();
                        //     }
                        // }
                    });
                }
            });
        });

        $('body').on('click', '#offlineButton, #stripePaymentButton, #razorpayButton', function(){
            if ($('a').hasClass('disabled')) {
                $('a').removeClass('disabled');
            } else {
                $('a').addClass('disabled');
            }
        });

        function feedback () {
            let bookingId = '{{$booking->id }}';
            let current_url = "?current_url="+'{{$current_url}}';
            let url = "{{ route('admin.bookings.feedBack', ':id') }}"+current_url;
            url = url.replace(':id', bookingId);

            $(modal_default + ' ' + modal_heading).html('...');
            $.ajaxModal(modal_default, url);
        };

        /* function feedback () {
            let bookingId = '{{$booking->id }}';
            let current_url = "?current_url="+'{{$current_url}}';
            let url = "{{ route('admin.bookings.feedBack', ':id') }}"+current_url;
            url = url.replace(':id', bookingId);

            @if ($current_url == 'bookingPage')
                $.easyAjax({
                    type: 'GET',
                    url: url,
                    success: function (response) {
                        if (response.status == "success") {
                            $('#booking-detail').show().html(response.view).fadeIn('slow');
                        }
                    }
                });
            @else
                $.ajaxModal(modal_lg, url);
            @endif
        }; */

        $('body').on('click', '.add-payment', function() {
            let total = $('#total-amount').html();
            var totalRemaining = $('#total-remaining').html();
            var url = "{{ route('admin.pos.show-checkout-modal', ':amount') }}";
            url = url.replace(':amount', total);
            url = `${url}/${totalRemaining}`;
            $(modal_default + ' ' + modal_heading).html('...');
            $.ajaxModal(modal_default, url);
        });

        $('body').on('keyup', '#cash-given', function() {
            let cashGiven = $(this).val();
            if(cashGiven === ''){
                cashGiven = 0;
            }

            let total = $('#remaining').val();
            total = total.slice(1);
            total = parseFloat(total);
            let cashReturn = (parseFloat(total) - parseFloat(cashGiven)).toFixed(2);
            let cashRemaining = (parseFloat(total) - parseFloat(cashGiven)).toFixed(2);

            if(cashRemaining < 0){
                cashRemaining = parseFloat(0).toFixed(2);
            }

            if(cashReturn < 0){
                cashReturn = Math.abs(cashReturn);
            }
            else{
                cashReturn = parseFloat(0).toFixed(2);
            }

            $('#cash-return').html(currency_format(cashReturn));
            $('#cash-remaining').html(currency_format(cashRemaining));
            $('#pending-amount').val((cashRemaining));
        });

        $('body').on('click', '#submit-cart', function() {
            let bookingId = $('#add-payment').data('booking-id');
            let amountPaid = parseFloat($('#cash-given').val());

            if(isNaN(amountPaid))
            {
                swal('@lang("modules.booking.amountNotNull")');
                $('#user-error').html('@lang("modules.booking.amountNotNull")');
                return false;
            }
            else{
                $('#user-error').html('');
            }
            
            let total = $('#remaining').val();
            total = total.slice(1);
            // total = parseFloat(total);
            
            let cartTotal = $('#payment-modal-total').html();
            cartTotal = cartTotal.slice(1);
            cartTotal = parseFloat(cartTotal);
            paymentMode = $("input[name='payment_gateway']").val();

            if(amountPaid > total)
            {
                swal('@lang("modules.booking.amountNotMore")');

                $('#user-error').html('@lang("modules.booking.amountNotMore")');
                return false;
            }
            else{
                $('#user-error').html('');
            }
            let url = "{{route('admin.bookings.add-payment')}}";
            $.easyAjax({
                url: url,
                type: "GET",
                data:{'bookingId' : bookingId, 'amountPaid' : amountPaid, 'amountPending' : total, 'total' : cartTotal, 'paymentMode' : paymentMode},
                redirect: true,
                success: function (response) {
                    if (response.status == "success") {
                        $('#myModalDefault').hide();
                        $('.modal-content').html(response.view);
                        table._fnDraw();
                    }
                }
            })
        });

        $('body').on('click', '.delete-row', function(){
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
                        var url = "{{ route('admin.bookings.destroy',':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {'_token': token, '_method': 'DELETE'},
                        });
                    }
                });
        });
    </script>

    <script>
        {{-- @if($booking->coupon_discount > 0)
            $('body').on('click', '.show-coupon', function() {
                var url = '{{ route('admin.coupons.show', $booking->coupon_id)}}';
                $(modal_lg + ' ' + modal_heading).html('Show Coupon');
                $.ajaxModal(modal_lg, url);
            });
            @endif --}}
    </script>

    <!-- Paypal -->
    @include('front.partials.paypal_back_js')

    @if($credentials->stripe_status == 'active' && $current_emp_role->name == 'customer')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            checkAmount();

            function checkAmount(){

                let companyId = '{{ $booking->company->id }}';
                let amountToPay = '{{ $booking->formated_amount_to_pay }}';

                $.easyAjax({
                    url: '{{route('front.checkAmount')}}',
                    type: "GET",
                    data: {"_token" : "{{ csrf_token() }}", 'companyId' : companyId, 'amountToPay' : amountToPay },
                    success: function(response){
                        if (response.amount < response.usdAmount) {
                            $(".stripePayButton").addClass("d-none");
                        }
                    }
                });
            }

            var stripe = Stripe('{{ $credentials->stripe_client_id }}');
            var checkoutButton = document.getElementById('stripePaymentButton');
            var current_url = '{{ $current_url }}';

            if (checkoutButton) {
                checkoutButton.addEventListener('click', function() {
                    $.easyAjax({
                        url: '{{route('front.stripe')}}',
                        container: '#invoice_container',
                        type: "POST",
                        redirect: true,
                        async: false,
                        data: {
                            "_token" : "{{ csrf_token() }}",
                            'booking_id' : "{{$booking->id }}",
                            'return_url' :  current_url,
                        },
                        success: function(response){
                            stripe.redirectToCheckout({
                                sessionId: response.id,
                            }).then(function (result) {
                                if (result.error) {
                                    $.easyAjax({
                                        url: '{{route('front.redirectToErrorPage')}}',
                                    });
                                }
                            });
                        },
                    });
                });
            }
        </script>
    @endif

    @if($credentials->razorpay_status == 'active' && $current_emp_role->name == 'customer')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
            // put customer in razorpay payment flow
            var options = {
                key: "{{ $credentials->razorpay_key }}", // Enter the Key ID generated from the Dashboard
                amount: "{{ $booking->amount_to_pay * 100 }}", // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
                currency: "{{ $settings->currency->currency_code }}",
                name: "{{ $booking->user->name }}",
                description: "@lang('app.booking') @lang('front.headings.payment')",
                image: "{{ $setting->logo_url }}",
                order_id: "{{ $booking->order_id }}", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
                current_url: "{{ $current_url }}",

                "handler": function (response){
                    $.easyAjax({
                        url: "{{ route('front.razorpay.verifyPayment') }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            order_id: '{{ $booking->order_id }}',
                            razorpay_signature: response.razorpay_signature,
                            return_url: current_url,
                        },
                        container: '#invoice_container',
                        redirect: true,

                    })
                },
                prefill: {
                    name: "{{ $booking->user->name }}",
                    email: "{{ $booking->user->email }}",
                    contact: "{{ $booking->user->mobile }}"
                },
                notes: {
                    booking_id: "{{ $booking->id }}"
                },
                theme: {
                    color: "{{ $frontThemeSettings->primary_color }}"
                }
            };
            var rzp1 = new Razorpay(options);
            document.getElementById('razorpayButton').onclick = function(e){
                rzp1.open();
                e.preventDefault();
            }
        </script>
    @endif

    @include("partials.backend.currency_format")
@endpush

