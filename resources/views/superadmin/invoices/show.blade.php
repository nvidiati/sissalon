<div class="tab-pane active" id="subcriptionInvoices">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="myTable" class="table w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('app.company')</th>
                        <th>@lang('app.package')</th>
                        <th>@lang('modules.payments.transactionId')</th>
                        <th>@lang('app.amount')</th>
                        <th>@lang('app.date')</th>
                        <th>@lang('modules.billing.nextPaymentDate')</th>
                        <th>@lang('modules.payments.paymentGateway')</th>
                        <th class="text-right">@lang('app.action')</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="tab-pane" id="bookingsInvoices">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-center justify-content-md-start mb-3">
                <h6 ><i class="fa fa-angle-double-right"></i> @lang('app.bookingsInvoiceNote')</h6>
            </div>
            <div class="table-responsive">
                <table id="bookingInvoicesTable" class="table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('app.company')</th>
                            <th>@lang('app.transactionId')</th>
                            <th>@lang('app.amount')</th>
                            <th>@lang('app.application_fee')</th>
                            <th>@lang('app.method')</th>
                            <th>@lang('app.paid_on')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="tab-pane" id="offlineInvoices">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-center justify-content-md-start mb-3">
                <h6 ><i class="fa fa-angle-double-right"></i> @lang('app.offlineInvoiceNote')</h6>
            </div>
            <div class="table-responsive">
                <table id="offlineInvoicesTable" class="table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('app.company')</th>
                            <th>@lang('app.totalEarning')</th>
                            <th>@lang('app.commissionAmount')</th>
                            <th>@lang('app.paidAmount')</th>
                            <th>@lang('app.pendingAmount')</th>
                            <th>@lang('app.paidOn')</th>
                            <th>@lang('app.status')</th>
                            <th class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@push('footer-js')
<script>
    
    $(document).ready(function() {
        var table = $('#myTable').dataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            destroy: true,
            ajax: '{!! route('superadmin.invoices.data') !!}',
            language: languageOptions(),
            "fnDrawCallback": function( oSettings ) {
                $("body").tooltip({
                    selector: '[data-toggle="tooltip"]'
                });
                $('.role_id').select2({
                    width: '100%'
                });
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'company', name: 'company'},
                { data: 'package', name: 'package' },
                { data: 'transaction_id', name: 'transaction_id'},
                { data: 'amount', name: 'amount' },
                { data: 'paid_on', name: 'paid_on' },
                { data: 'next_pay_date', name: 'next_pay_date' },
                { data: 'method', name: 'method' },
                { data: 'action', name: 'action' }
            ]
        });
        new $.fn.dataTable.FixedHeader( table );

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
                    var url = "{{ route('superadmin.packages.destroy',':id') }}";
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
                            }
                        }
                    });
                }
            });
        });
    });

    // Fetch Booking Invoices (Not transferred to admin)
    $(document).ready(function() {
        var table = $('#bookingInvoicesTable').dataTable({
            responsive: true,
            serverSide: true,
            ajax: '{!! route('superadmin.bookingInvoice') !!}',
            language: languageOptions(),
            "fnDrawCallback": function( oSettings ) {
                $("body").tooltip({
                    selector: '[data-toggle="tooltip"]'
                });
            },
            columns: [
                { data: 'DT_RowIndex'},
                { data: 'company', name: 'company' },
                { data: 'transactionId', name: 'transactionId' },
                { data: 'amount', name: 'amount' },
                { data: 'application_fee', name: 'application_fee' },
                { data: 'method', name: 'method' },
                { data: 'paid_on', name: 'paid_on' },
            ]
        });
        new $.fn.dataTable.FixedHeader( table );
    });

    // Fetch offline booking commission
    $(document).ready(function() {
        var offlineTable = $('#offlineInvoicesTable').dataTable({
            responsive: true,
            serverSide: true,
            ajax: '{!! route('superadmin.offlineInvoice') !!}',
            language: languageOptions(),
            "fnDrawCallback": function( oSettings ) {
                $("body").tooltip({
                    selector: '[data-toggle="tooltip"]'
                });
            },
            columns: [
                { data: 'DT_RowIndex'},
                { data: 'company', name: 'company' },
                { data: 'total_earning', name: 'total_earning' },
                { data: 'commission_amount', name: 'commission_amount' },
                { data: 'paid_amount', name: 'paid_amount' },
                { data: 'pending_amount', name: 'pending_amount' },
                { data: 'paid_on', name: 'paid_on' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action' },
            ]
        });
        new $.fn.dataTable.FixedHeader( offlineTable );
    });

    $('body').on('click', '.edit-invoice', function() {
        var id = $(this).data('invoice-id');
        var url = "{{ route('superadmin.invoices.edit',':id') }}";
        url = url.replace(':id', id);
        $(modal_lg + ' ' + modal_heading).html('...');
        $.ajaxModal(modal_lg, url);
    });
</script>
@endpush