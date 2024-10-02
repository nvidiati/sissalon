<?php

namespace App\Http\Controllers\Admin;

use App\Payment;
use Carbon\Carbon;
use App\Commission;
use App\Http\Controllers\AdminBaseController;

class InvoiceController extends AdminBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('menu.invoices'));
    }

    public function index()
    {
        $this->offlineCommission = Commission::where('gateway', 'cash')->orWhere('gateway', 'card')->get();
        
        if (\request()->ajax()) {
            return \datatables()->of($this->offlineCommission)
                ->editColumn('total_earning', function ($row) {
                    return currencyFormatter($row->total_amount, $row->currency->currency_symbol);
                })
                ->editColumn('commission_amount', function ($row) {
                    return currencyFormatter($row->commission_amount, $row->currency->currency_symbol);
                })
                ->editColumn('paid_amount', function ($row) {
                    return currencyFormatter($row->deposit_amount, $row->currency->currency_symbol);
                })
                ->editColumn('pending_amount', function ($row) {
                    return currencyFormatter($row->pending_amount, $row->currency->currency_symbol);
                })
                ->editColumn('status', function ($row) {

                    if ($row->status == 'settled') {
                        return '<span class="badge badge-success">'.$row->status.'</span>';
                    }
                    else {
                        return '<span class="badge badge-warning">'.$row->status.'</span>';
                    }
                })
                ->editColumn('paid_on', function ($row) {
                    return Carbon::parse($row->paid_on)->translatedFormat($this->settings->date_format);
                })
                ->addIndexColumn()
                ->rawColumns(['status'])
                ->toJson();
        }

        $this->totalAmount = Payment::where('gateway', 'RazorPay')->orWhere('gateway', 'PayPal')->orWhere('gateway', 'Stripe')->where('status', 'completed')->where('transfer_status', 'transferred')->sum('amount');
        $this->totalCommission = Payment::where('gateway', 'RazorPay')->orWhere('gateway', 'PayPal')->orWhere('gateway', 'Stripe')->where('status', 'completed')->where('transfer_status', 'transferred')->sum('commission');

        return view('admin.invoices.index', $this->data);
    }

}
