@extends('layouts.master')

@section('content')

    <div class="row">
        <!-- Tabs  -->
        <div class="col-md-12">
            <ul class="nav nav-tabs">
                <li class="nav-item active">
                    <a class="nav-link active" href="#subcriptionInvoices" data-toggle="tab">@lang('app.SubscriptionInvoices')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#bookingsInvoices" data-toggle="tab">@lang('app.bookingsInvoices')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#offlineInvoices" data-toggle="tab">@lang('app.offlineInvoices')</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 tab-content">
            @include('superadmin/invoices/show')
        </div>
    </div>

@endsection

@push('footer-js')
<script>
    //
</script>
@endpush
