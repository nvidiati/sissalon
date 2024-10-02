@extends('layouts.master')

@section('content')

@section('content')

    <div class="row">
        <!-- Tabs  -->
        <div class="col-md-12">
            <ul class="nav nav-tabs">
                <li class="nav-item active">
                    <a class="nav-link active" href="#onlineInvoices" data-toggle="tab">@lang('app.onlineBookingCommission')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#offlineInvoices" data-toggle="tab">@lang('app.offlineInvoices')</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 tab-content">
            <div class="tab-pane active" id="onlineInvoices">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-center justify-content-md-start mb-3">
                            <h6 ><i class="fa fa-angle-double-right"></i> @lang('app.onlineInvoiceNote')</h6>
                        </div>
                        <div class="table-responsive">
                            <table id="onlineInvoicesTable" class="table w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>@lang('app.totalOnlineEarning')</th>
                                        <th>@lang('app.total') @lang('app.commission')</th>
                                        <th>@lang('app.status')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td>{{ currencyFormatter(number_format((float) ($totalAmount), 2, '.', ''), myCurrencySymbol()) }}</td>
                                        <td>{{ currencyFormatter(number_format((float) ($totalCommission), 2, '.', ''), myCurrencySymbol()) }}</td>
                                        <td>@lang('app.paid')</td>
                                    </tr>
                                </tbody>
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
                                        <th>@lang('app.totalOfflineEarning')</th>
                                        <th>@lang('app.total') @lang('app.commission')</th>
                                        <th>@lang('app.paid') @lang('app.commission')</th>
                                        <th>@lang('app.pending') @lang('app.commission')</th>
                                        <th>@lang('app.paidOn')</th>
                                        <th>@lang('app.status')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('footer-js')
    <script>
        $(document).ready(function() {
            var offlineTable = $('#offlineInvoicesTable').dataTable({
                responsive: true,
                serverSide: true,
                ajax: '{!! route('admin.invoices.index') !!}',
                language: languageOptions(),
                "fnDrawCallback": function( oSettings ) {
                    $("body").tooltip({
                        selector: '[data-toggle="tooltip"]'
                    });
                },
                columns: [
                    { data: 'DT_RowIndex'},
                    { data: 'total_earning', name: 'total_earning' },
                    { data: 'commission_amount', name: 'commission_amount' },
                    { data: 'paid_amount', name: 'paid_amount' },
                    { data: 'pending_amount', name: 'pending_amount' },
                    { data: 'paid_on', name: 'paid_on' },
                    { data: 'status', name: 'status' },
                ]
            });
            new $.fn.dataTable.FixedHeader( offlineTable );
        });
    </script>
@endpush
