@extends('layouts.master')

@push('head-css')
<style>
    #myTable td {
        padding: 0;
    }

    .status {
        font-size: 80%;
    }

    .booking-selected {
        border: 3px solid var(--main-color);
    }

    .payments a {
        border: 2px solid;
    }

    #filter-sort {
        width: 100%
    }

    #myTable tbody tr td {
        padding-top: 20px !important;
        padding-bottom: 15px !important;
    }

    #myTable tbody tr td:nth-child(4) {
        text-align: right !important;
    }

    #myTable tbody tr td:nth-child(5) {
        text-align: center !important;
    }

    #myModalDefault {
        z-index: 9999;
    }
</style>

@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="col-md-12">
            <div class="card card-light">
                @if(($user->is_admin || $user->is_employee) && !\Session::get('loginRole') && ($current_emp_role->name == 'administrator' || $current_emp_role->name == 'employee'))
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md">
                                <div class="form-group">
                                    <select name="" id="filter-status" class="form-control">
                                        <option value="">@lang('app.filter') @lang('app.status'): @lang('app.viewAll')
                                        </option>
                                        <option @if($status=='completed' ) selected @endif value="completed">
                                            @lang('app.completed')</option>
                                        <option @if($status=='pending' ) selected @endif value="pending">
                                            @lang('app.pending')</option>
                                        @if ($user->is_employee || $current_emp_role->name == 'employee')
                                        <option @if($status=='assignedpending' ) selected @endif value="assignedpending">
                                            @lang('modules.dashboard.assignedPending')</option>
                                        @endif
                                        <option @if($status=='approved' ) selected @endif value="approved">
                                            @lang('app.approved')</option>
                                        <option @if($status=='in progress' ) selected @endif value="in progress">
                                            @lang('app.in progress')</option>
                                        <option @if($status=='canceled' ) selected @endif value="canceled">
                                            @lang('app.canceled')</option>
                                    </select>
                                </div>
                            </div>
                            @permission('create_booking')
                                <div class="col-md">
                                    <div class="form-group">
                                        <select name="" id="filter-customer" class="form-control select2">
                                            <option value="">@lang('modules.booking.selectCustomer'): @lang('app.viewAll')
                                            </option>
                                            @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ ucwords($customer->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="form-group">
                                        <select name="" id="filter-location" class="form-control select2">
                                            <option value="">@lang('modules.booking.selectLocation'): @lang('app.viewAll')
                                            </option>
                                            @foreach($locations as $location)
                                            <option value="{{ $location->id }}">{{ ucwords($location->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="form-group">
                                        <input type="text" class="form-control datepicker" name="filter_date" id="filter-date"
                                            placeholder="@lang('app.booking') @lang('app.date')">
                                        <input type="hidden" name="startDate" id="startDate" value="{{request('startDate')}}">
                                        <input type="hidden" name="endDate" id="endDate" value="{{request('endDate')}}">
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="form-group">
                                        <select selected name="filter-sort" id="filter-sort" class="form-control select2">
                                            <option value="">@lang('modules.booking.sortBy')</option>
                                            <option value="desc">@lang('modules.booking.sort.desc') </option>
                                            <option value="asc">@lang('modules.booking.sort.asc')</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="form-group">
                                        <select selected name="filter-type" id="filter-type" class="form-control select2">
                                            <option value="">@lang('modules.booking.selectType'): @lang('app.viewAll')</option>
                                            <option value="offline">@lang('app.serviceOffline') </option>
                                            <option value="online">@lang('app.serviceOnline')</option>
                                        </select>
                                    </div>
                                </div>
                            @endpermission

                            <div class="col-md">
                                <div class="form-group">
                                    <button type="button" id="reset-filter" class="btn btn-danger"><i
                                        class="fa fa-times"></i> @lang('app.reset')</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-md">
                        <div class="form-group">
                            <input type="hidden" name="filter-status" id="filter-status" value="{{request('status')}}">
                            <input type="hidden" name="startDate" id="startDate" value="{{request('startDate')}}">
                            <input type="hidden" name="endDate" id="endDate" value="{{request('endDate')}}">
                        </div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>@lang('app.customerName')</th>
                                    <th>@lang('app.bookingTime')</th>
                                    <th class="text-right">@lang('app.total')</th>
                                    <th class="text-center">@lang('app.status')</th>
                                    <th class="text-right">@lang('app.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('footer-js')
    @if($credentials->stripe_status == 'active' && !$user->is_admin)
        <script src="https://js.stripe.com/v3/"></script>
    @endif

    @if($credentials->razorpay_status == 'active' && !$user->is_admin)
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif

    <script>
        $(document).ready(function() {

            $('.select2').select2();

            $('.datepicker').datetimepicker({
                format: '{{ $date_picker_format }}',
                locale: '{{ $settings->locale }}',
                allowInputToggle: true,
                icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-angle-double-left",
                    next: "fa fa-angle-double-right",
                },
                useCurrent: false,
            }).on("dp.change", function (e) {
                $('#startDate').val( moment(e.date).format('YYYY-MM-DD'));
                table._fnDraw();
            });

            function updateBooking(currEle) {
                let url = '{{route('admin.bookings.update', ':id')}}';
                url = url.replace(':id', currEle.data('booking-id'));
                $.easyAjax({
                    url: url,
                    container: '#update-form',
                    type: "POST",
                    data: $('#update-form').serialize(),
                    success: function (response) {
                        if (response.status == "success") {
                            $('#booking-detail').hide().html(response.view).fadeIn('slow');
                            table._fnDraw();
                        }
                    }
                })
            }

            $('body').on('click', '#update-booking', function () {
                let cartItems = $("input[name='item_prices[]']").length;
                let product_cartItems=$("input[name='product_prices[]']").length;

                if(cartItems === 0 && product_cartItems === 0){
                    swal('@lang("modules.booking.addItemsToCart")');
                    $('#cart-item-error').html('@lang("modules.booking.addItemsToCart")');

                    return false;
                }
                else {
                    $('#cart-item-error').html('');
                    var updateButtonEl = $(this);
                    if ($('#booking-status').val() == 'completed' && $('#payment-status').val() == 'pending' && $('.fa.fa-money').parent().text().indexOf('cash') !== -1) {
                        swal({
                            text: '@lang("modules.booking.changePaymentStatus")',
                            closeOnClickOutside: false,
                            buttons: [
                                'NO', 'YES'
                            ]
                        }).then(function (isConfirmed) {
                            if (isConfirmed) {
                                $('#payment-status').val('completed');
                            }
                            updateBooking(updateButtonEl);
                        });
                    }
                    else {
                        updateBooking(updateButtonEl);
                    }
                }

            });

            var table = $('#myTable').dataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: {
                    'url' : '{!! route('admin.bookings.index') !!}',
                    data: function(data) {
                        data.filter_sort = $('#filter-sort').val();
                        data.filter_status = $('#filter-status').val();
                        data.filter_type = $('#filter-type').val();
                        data.startDate = $('#startDate').val();
                        data.endDate = $('#endDate').val();
                        data.filter_customer = $('#filter-customer').val();
                        data.filter_location = $('#filter-location').val();
                    }
                },
                language: languageOptions(),
                "fnDrawCallback": function( oSettings ) {
                    $("body").tooltip({
                        selector: '[data-toggle="tooltip"]'
                    });
                },
                order: [[0, 'DESC']],
                columns: [
                    { data: 'DT_RowIndex'},
                    { data: 'name'},
                    { data: 'date_time'},
                    { data: 'amount_to_pay'},
                    { data: 'status'},
                    { data: 'action'},
                ]
            });
            new $.fn.dataTable.FixedHeader( table );

            $('body').on('click', '#change-status', function(){
                $.easyAjax({
                    url: '{{route('admin.bookings.multiStatusUpdate')}}',
                    container: '#createForm',
                    type: "POST",
                    data: $('#createForm').serialize(),
                    success: function(response){
                        table._fnDraw();
                        $('#change-status').attr('disabled', true);
                    }
                })
            });

            $('body').on('click', '#change_status', function(){
                if ($(this).hasClass('is-invalid')){
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            })

            $('body').on('click', '.delete-row', function(){
                var id = $(this).data('row-id');
                swal({
                    icon: "warning",
                    buttons: ["@lang('app.cancel')", "@lang('app.ok')"],
                    dangerMode: true,
                    title: "@lang('errors.areYouSure')",
                    text: "@lang('errors.deleteWarning')",
                }).then((willDelete) => {
                    if (willDelete) {
                        var url = "{{ route('admin.bookings.destroy',':id') }}";
                        url = url.replace(':id', id);

                        var token = "{{ csrf_token() }}";

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            data: {'_token': token, '_method': 'DELETE'},
                            success: function (response) {
                                if (response.status == "success") {
                                    $.unblockUI();
                                    table._fnDraw();
                                    $('#booking-detail').html('');
                                }
                            }
                        });
                    }
                });
            });

            $('body').on('click', '.edit-booking', function () {
                let bookingId = $(this).data('booking-id');
                let current_url = "?current_url="+'bookingPage';
                let url = "{{ route('admin.bookings.edit', ':id') }}"+current_url;
                url = url.replace(':id', bookingId);

                $.easyAjax({
                    type: 'GET',
                    url: url,
                    success: function (response) {
                        if (response.status == "success") {
                            $('.modal-content').hide().html(response.view).fadeIn('slow');
                        }
                    }
                });
            });

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

                $('#cash-return').html(cashReturn);
                $('#cash-remaining').html(cashRemaining);
                $('#pending-amount').val(cashRemaining);
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
                total = parseFloat(total);
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

            $('#filter-status, #filter-customer, #filter-location, #filter-sort, #filter-type').change(function () {
                table._fnDraw();
            });

            $('body').on('click', '#reset-filter', function () {
                $('#filter-status, #filter-date').val('');
                $("#filter-customer").val('').trigger('change');
                $("#filter-location").val('').trigger('change');
                $("#startDate").val('').trigger('change');
                $('#filter-sort').val('').trigger('change');
                $('#filter-type').val('').trigger('change');
                table._fnDraw();
            })

            $('body').on('click', '.send-reminder', function () {
                let bookingId = $(this).data('booking-id');

                $.easyAjax({
                    type: 'POST',
                    url: '{{ route("admin.bookings.sendReminder") }}',
                    data: {bookingId: bookingId, _token: '{{ csrf_token() }}'}
                });
            });

        });
    </script>
    @if($user->is_admin)
        <script>
            $('#myTable').on('click', '.booking-div', function(){

                let checkbox = $(this).closest('.row').find('.booking-checkboxes');

                if(checkbox.is(":checked")){
                    checkbox.removeAttr('checked');
                    $(this).closest('.row').removeClass('booking-selected');
                }
                else{
                    checkbox.attr('checked', true);
                    $(this).closest('.row').addClass('booking-selected');
                }

                $('#selected-booking-count').html($('[name="booking_checkboxes[]"]:checked').length)

                if($('[name="booking_checkboxes[]"]:checked').length > 0){
                    $('#change-status').removeAttr('disabled');
                }
                else{
                    $('#change-status').attr('disabled', true);
                }
            });
        </script>
    @endif

    @if (Session::has('success'))
        <script>
            toastr.success("{!!  Session::get('success') !!}");
            {{ Session::forget('success') }};
        </script>
    @endif

    <script>
        $('body').on('click', '.view-deal', function() {
            var id = $(this).data('row-id');
            var url = "{{ route('admin.deals.show',':id') }}";
            url = url.replace(':id', id);

            $(modal_lg + ' ' + modal_heading).html('...');
            $.ajaxModal(modal_lg, url);
        });
    </script>

@endpush
