<?php

namespace App\Http\Controllers\Admin;

use App\Tax;
use App\User;
use App\Coupon;
use App\Rating;
use App\Booking;
use App\Company;
use App\ItemTax;
use App\Payment;
use App\Product;
use App\Location;
use Carbon\Carbon;
use App\Commission;
use App\BookingItem;
use App\ZoomMeeting;
use App\Helper\Reply;
use App\GlobalSetting;
use App\BusinessService;
use Illuminate\Http\Request;
use App\Scopes\CompanyScope;
use App\PaymentGatewayCredentials;
use Illuminate\Support\Facades\DB;
use App\Notifications\BookingCancel;
use Illuminate\Support\Facades\Auth;
use App\Notifications\BookingReminder;
use App\Http\Requests\Booking\UpdateBooking;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\BookingStatusMultiUpdate;

class BookingController extends AdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->credentials = PaymentGatewayCredentials::first();
        $setting = Company::with('currency')->first();

        view()->share('pageTitle', __('menu.bookings'));
        view()->share('credentials', $this->credentials);
        view()->share('setting', $setting);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_booking') && !$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_booking'), 403);

        if (\request()->ajax()) {

            $bookings = Booking::orderBy('date_time', 'desc')
                ->with([
                    'user' => function ($q) {
                        $q->withoutGlobalScope(CompanyScope::class);
                    },
                    'bookingPayments' => function ($q) {
                        $q->withoutGlobalScope(CompanyScope::class);
                    }
                ]);

            if (\request('filter_sort') != '') {
                $bookings->orderBy('id', \request('filter_sort'));
            }

            if (\request('filter_type') != '') {
                $bookings->where('booking_type', \request('filter_type'));
            }

            if (\request('filter_status') != '' || \request('filter_status') != null) {
                $bookings->where('status', \request('filter_status'));
            }

            if ((request()->status != '' || request()->status != null) && request()->status != 'inprogress' && request()->status != 'onlinebookings' && request()->status != 'walkinbookings') {
                $bookings->where('status', request()->status);
            }

            if (\request('startDate') != '' && \request('endDate') != '') {
                $startDate = Carbon::parse(\request('startDate'))->format('Y-m-d');
                $endDate = Carbon::parse(\request('endDate'))->format('Y-m-d');

                $bookings->whereDate('date_time', '>=', $startDate)->whereDate('date_time', '<=', $endDate);
            }

            if (request()->status == 'inprogress') {
                $bookings->where('status', 'in progress');
            }

            if (request()->status == 'onlinebookings') {
                $bookings->where('source', 'online');
            }

            if (request()->status == 'walkinbookings') {
                $bookings->where('source', 'pos');
            }

            if (\request('filter_customer') != '') {
                $customer = request()->filter_customer;
                $bookings->where('user_id', $customer);
            }

            if (\request('filter_location') != '') {
                $bookings->leftJoin('booking_items', 'bookings.id', 'booking_items.booking_id')
                    ->leftJoin('business_services', 'booking_items.business_service_id', 'business_services.id')
                    ->leftJoin('locations', 'business_services.location_id', 'locations.id')
                    ->select('bookings.*')
                    ->where('locations.id', request('filter_location'))
                    ->groupBy('bookings.id');
            }

            if (\request('filter_date') != '') {
                $startTime = Carbon::createFromFormat('d-m-Y', request('filter_date'), $this->settings->timezone)->setTimezone('UTC')->startOfDay();
                $endTime = $startTime->copy()->addDay()->subSecond();

                $bookings->whereBetween('bookings.date_time', [$startTime, $endTime]);
            }

            if ($this->user->is_employee && $this->user->isAbleTo('read_booking'))
            {
                if ($this->user->is_employee && $this->current_emp_role->name == 'employee')
                {
                    $bookings->whereHas('users', function ($q) {
                        $q->where('user_id', $this->user->id);
                    })->orWhere('user_id', $this->user->id);
                }
                else {
                    $bookings->where('user_id', $this->user->id);
                }
            }
            
            if ($this->user->is_employee && $this->current_emp_role->name == 'employee' && (!is_null(\request('filter_status')) || !is_null(request()->status)))
            {
                if (session('loginRole')) {
                    $bookings->where('user_id', $this->user->id);
                }
            }

            if (!$this->user->is_admin && !$this->user->isAbleTo('create_booking')) {

                if ($this->user->is_employee && $this->current_emp_role->name == 'employee' && session('loginRole'))
                {
                    $bookings->whereHas('users', function ($q) {
                        $q->where('user_id', $this->user->id);
                    })->orWhere('user_id', $this->user->id);
                }
                else {
                    $bookings->where('user_id', $this->user->id);
                }
            }

            if ($this->current_emp_role->name == 'customer' && session('loginRole')) {
                $bookings->where('user_id', $this->user->id);
            }

            if (request()->status == 'assignedpending' && $this->current_emp_role->name == 'employee' && \request('startDate') != '' && \request('endDate') != '') {
                $startDate = Carbon::createFromFormat('d-m-Y', \request('startDate'));
                $endDate = Carbon::createFromFormat('d-m-Y', \request('endDate'));

                $bookings = Booking::selectRaw('bookings.*')->whereDate('bookings.date_time', '>=', $startDate)->whereDate('bookings.date_time', '<=', $endDate)
                    ->where('bookings.status', 'pending')->join('booking_user', 'bookings.id', 'booking_user.booking_id')->where('booking_user.user_id', $this->user->id);
            }

            $bookings = $bookings->get();
            $settings = GlobalSetting::first();


            return \datatables()->of($bookings)
                ->addColumn('name', function ($row) {
                    return ucfirst($row->user->name);
                })
                ->editColumn('date_time', function ($row) use ($settings) {
                    return Carbon::parse($row->date_time)->setTimezone($row->location->timezone->zone_name)->format($settings->date_format.' '.$settings->time_format);
                })
                ->editColumn('amount_to_pay', function ($row) {
                    $data = '<span> <label style="font-weight:bold;">Total = </label>'.currencyFormatter($row->amount_to_pay, myCurrencySymbol()).'</span><br>';

                    $paymentCount = $row->bookingPayments->count();
                    $amountPaid = 0;
                    $pending = $row->amount_to_pay;

                    if($paymentCount > 0)
                    {
                        $amountPaid = $row->bookingPayments->sum('amount_paid');
                        $pending = $row->amount_to_pay - $amountPaid;
                    }

                    $data .= '<span class="text-success"> <label style="font-weight:bold;">Paid = </label>'.currencyFormatter((float)$amountPaid, myCurrencySymbol()).'</span><br>';


                    $data .= '<span class="text-danger"> <label style="font-weight:bold;">Pending = </label>'.currencyFormatter((float)$pending, myCurrencySymbol()).'</span><br>';
                    return $data;
                })
                ->editColumn('status', function ($row)
                {
                    if($row->status == 'completed'){
                        return '<label class="badge badge-success text-center">'.ucfirst($row->status).'</label>';
                    }
                    elseif($row->status == 'pending'){
                        return '<label class="badge badge-warning text-center">'.ucfirst($row->status).'</label>';
                    }
                    elseif($row->status == 'in progress'){
                        return '<label class="badge badge-primary text-center">'.ucfirst($row->status).'</label>';
                    }
                    elseif($row->status == 'approved'){
                        return '<label class="badge badge-info text-center">'.ucfirst($row->status).'</label>';
                    }
                    elseif($row->status == 'canceled'){
                        return '<label class="badge badge-danger text-center">'.ucfirst($row->status).'</label>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $action = '<div class="text-right">';

                    if (($this->current_emp_role->name != 'customer') && $this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_booking') && $row->status !== 'completed') {
                        $action .= '<a href="' . route('admin.bookings.edit', [$row->id]) . '" class="btn btn-primary btn-circle"
                          data-toggle="tooltip" data-original-title="'.__('app.edit').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                    }

                    $action .= ' <a href="'.route('admin.bookings.show', [$row->id]).'" data-booking-id="' . $row->id . '" class="btn btn-info btn-circle "
                    data-toggle="tooltip" data-original-title="'.__('app.view').'"><i class="fa fa-eye" aria-hidden="true"></i></a> ';

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_booking')) {
                        $action .= ' <a href="javascript:;" class="btn btn-danger btn-circle delete-row"
                          data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="'.__('app.delete').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                    }

                    $action .= '</div>';

                    return $action;
                })
                ->addIndexColumn()
                ->rawColumns(['name', 'mobile', 'status', 'action', 'amount_to_pay'])
                ->make(true);
        }

        $customers = User::withoutGlobalScopes()->has('customerBookings')->get();
        $locations = Location::all();
        $status = request()->status;

        return view('admin.booking.index', compact('status', 'customers', 'locations'));
    }

    public function calendar()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_booking') && !$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_booking'), 403);
        $bookings = [];

        if ($this->user->hasRole('customer') || $this->current_emp_role->name == 'customer') {
            $bookings = Booking::with([
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class)->where('id', $this->user->id);
                }
            ])->where('status', '!=', 'canceled')->where('date_time', '!=', null)->where('user_id', $this->user->id)->get();
        }
        elseif ($this->user->hasRole('employee')) {
            $bookings = Booking::with([
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                }
            ])->where(function ($q) {
                $q->where('status', '!=', 'canceled')->where('date_time', '!=', null);
                $q->where(function ($q) {
                    $q->where('user_id', $this->user->id);
                    $q->orWhere(function ($q) {
                        $q->whereHas('users', function ($q) {
                            $q->where('id', $this->user->id);
                        });
                    });
                });
            })->get();
        }
        elseif ($this->user->is_admin) {
            $bookings = Booking::with([
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                }
            ])->where(function ($q) {
                $q->where('status', '!=', 'canceled');
                $q->where('date_time', '!=', null);
            })->get();
        }

        return view('admin.booking.calendar_index', compact('bookings'));
    }

    /**
     * show
     *
     * @param  Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_booking') && !$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_booking'), 403);

        $booking = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'coupon' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'user' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'bookingPayments' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            ])->find($id);

        $meeting = '';

        if($booking->booking_type === 'online')
        {
            $meeting = ZoomMeeting::where('booking_id', $booking->id)->first();
        }

        $current_url = ($request->current_url != null) ? $request->current_url : 'bookingPage';
        $commonCondition = $booking->payment_status == 'pending' && $booking->status != 'canceled' && $this->credentials->show_payment_options == 'show' && $this->current_emp_role->name == 'customer';
        $ratings = Rating::where('booking_id', $id)->get();
        $activePaypalAccountDetail = $booking->company->activePaypalAccountDetail ? $booking->company->activePaypalAccountDetail->account_id : null;
        /* @phpstan-ignore-next-line */
        $bookingPayments = $booking->bookingPayments;
        $totalPaid = $bookingPayments->sum('amount_paid');
        $totalPending = $booking->amount_to_pay - $totalPaid;

        if ($request->current_url == 'calendarPage') {
            return view('admin.booking.show', $this->data);
        }

        return view('admin.booking.show', compact('booking', 'meeting', 'current_url', 'commonCondition', 'ratings', 'totalPaid', 'totalPending', 'activePaypalAccountDetail'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        abort_if(!$this->user->isAbleTo('update_booking'), 403);

        $selected_booking_user = array();
        $booking_users = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            }
        ])->find($id);

        foreach ($booking_users->users as $key => $user)
        {
            array_push($selected_booking_user, $user->id);
        }

        $this->selected_booking_user = $selected_booking_user;

        $this->booking = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'user' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'deal' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'deal.location' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'items' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            }
        ])->find($id);

        $this->tax = Tax::active()->first();
        $this->employees = User::OtherThanCustomers()->get();
        $this->businessServices = BusinessService::active()->get();
        $this->products = Product::active()->get();
        $this->current_url = $request->current_url ? $request->current_url : 'calendarPage';

        if ($request->current_url == 'bookingPage' || $request->current_url == 'customerPage') {
            $view = view('admin.booking.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'view' => $view]);
        }

        return view('admin.booking.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateBooking $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBooking $request, $id)
    {
        abort_if(!$this->user->isAbleTo('update_booking'), 403);

        /* these are product varibles */
        $products       = $request->cart_products;
        $productQty     = $request->product_quantity;
        $productPrice   = $request->product_prices;

        $types          = $request->types;
        $employees      = $request->employee_id;
        $services       = $request->item_ids;
        $quantity       = $request->cart_quantity;
        $taxPrice       = $request->tax_amount;
        $prices         = $request->item_prices;
        $discount       = $request->cart_discount;
        $payment_status = $request->payment_status;
        $discountAmount = 0;
        $originalProductAmt = 0;
        $amountToPay    = 0;

        $originalAmount = 0;
        $bookingItems   = array();
        $productTax     = 0;
        $taxPercent     = 0;
        $tax            = 0;
        $taxAmount      = 0;
        $productTaxAmt  = 0;

        /* save services and deals */
        if (!is_null($services)) {
            foreach ($services as $key => $service) {
                $amount = ($quantity[$key] * $prices[$key]);

                $deal_id = ($types[$key] == 'deal') ? $services[$key] : null;
                $service_id = ($types[$key] == 'service') ? $services[$key] : null;

                $bookingItems[] = [
                    'business_service_id' => $service_id,
                    'quantity' => $quantity[$key],
                    'unit_price' => $prices[$key],
                    'amount' => $amount,
                    'deal_id' => $deal_id,
                ];

                $originalAmount = ($originalAmount + $amount);

                if ($types[$key] == 'deal') {
                    $taxes = ItemTax::with('tax')->where('deal_id', $deal_id)->get();
                }
                else {
                    $taxes = ItemTax::with('tax')->where('service_id', $service_id)->get();
                }

                $tax = 0;

                foreach ($taxes as $key => $value) {
                    $tax += $value->tax->percent;
                    $taxName[] = $value->tax->name;
                    $taxPercent += $value->tax->percent;
                }

                $taxAmount += ($amount * $tax) / 100;
            }
        }

        $productItems = [];
        /* save products */
        if (!is_null($products)) {
            foreach ($products as $key => $product) {
                $productAmt = ($productQty[$key] * $productPrice[$key]);

                $productItems[] = [
                    'product_id' => $product,
                    'quantity' => $productQty[$key],
                    'unit_price' => $productPrice[$key],
                    'amount' => $productAmt
                ];

                $originalProductAmt = ($originalProductAmt + $productAmt);

                $taxes = ItemTax::with('tax')->where('product_id', $product)->get();

                $productTax = 0;

                foreach ($taxes as $key => $value) {
                    $productTax += $value->tax->percent;
                    $taxName[] = $value->tax->name;
                    $taxPercent += $value->tax->percent;
                }

                $productTaxAmt += ($productAmt * $productTax) / 100;
            }
        }

        $totalTax = $taxAmount + $productTaxAmt;

        $amountToPay = $originalAmount;

        $booking = Booking::where('id', $id)
            ->with([
                'payment' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                },
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                },
            ])
            ->first();

        $taxAmount = 0;

        if ($discount > 0) {
            if ($discount > 100) {
                $discount = 100;
            }

            $discountAmount = (($discount / 100) * $originalAmount);
            $amountToPay = ($originalAmount - $discountAmount);
        }

        $amountToPay = ($amountToPay + $totalTax);

        if (!is_null($request->coupon_id))
        {
            if ($amountToPay <= $request->coupon_amount) {
                $amountToPay = 0;
            }
            else {
                $amountToPay -= $request->coupon_amount;
            }
        }

        if ($originalProductAmt > 0) {
            $amountToPay = ($amountToPay + $originalProductAmt);
        }

        $amountToPay = round($amountToPay, 2);

        $booking->date_time   = Carbon::createFromFormat('Y-m-d H:i a', $request->booking_date . ' ' . $request->hidden_booking_time)->format('Y-m-d H:i:s');
        $booking->status      = $request->status;
        $booking->original_amount = $originalAmount;
        $booking->product_amount = $originalProductAmt;
        $booking->discount = $discountAmount;
        $booking->discount_percent = $request->cart_discount;;
        $booking->tax_amount = $totalTax;
        $booking->amount_to_pay = $amountToPay;
        $booking->payment_status = $payment_status;

        if($payment_status === 'completed')
        {
            if($request->paid_amount < $amountToPay)
            {
                $booking->payment_status = 'pending';
            }
        }

        $booking->save();

        /* assign employees to this appointment */
        if (!empty($employees)) {
            $assignedEmployee   = array();

            foreach ($employees as $key => $employee) {
                $assignedEmployee[] = $employees[$key];
            }

            $booking = Booking::with([
                'payment' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                },
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                },
                'users' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                },
            ])->find($id);
            $booking->users()->sync($assignedEmployee);
        }

        // Delete old items and enter new booking_date
        BookingItem::where('booking_id', $id)->delete();

        $total_amount = 0.00;

        if (!is_null($services)) {

            foreach ($bookingItems as $key => $bookingItem) {
                $bookingItems[$key]['booking_id'] = $booking->id;
                $bookingItems[$key]['company_id'] = $booking->company_id;
                $total_amount += $bookingItem['amount'];
            }

            DB::table('booking_items')->insert($bookingItems);
        }

        $total_amt = 0.00;

        if (!is_null($products)) {

            foreach ($productItems as $key => $productItem) {
                $productItems[$key]['booking_id'] = $booking->id;
                $productItems[$key]['company_id'] = $booking->company_id;
                $total_amt += $productItem['amount'];
            }

            DB::table('booking_items')->insert($productItems);
        }

        $commissionPercentage = PaymentGatewayCredentials::withoutGlobalScopes()->first()->offline_commission;

        if (!$booking->bookingPayment) {
            $payment = new Payment();
            $payment->currency_id = $this->settings->currency_id;
            $payment->booking_id = $booking->id;
            $payment->amount = $amountToPay;
            $payment->gateway = 'cash';
            $payment->status = $payment_status;
            $payment->paid_on = Carbon::now();

            if (!is_null($commissionPercentage) && $commissionPercentage > 0) {
                $payment->commission = round((($amountToPay / 100) * $commissionPercentage), 2);
            }
        }
        else {
            $payment = $booking->bookingPayment;
            $payment->status = $payment_status;

            if (!is_null($commissionPercentage) && $commissionPercentage > 0 && $payment_status == 'completed') {
                $payment->commission = round((($amountToPay / 100) * $commissionPercentage), 0);
            }

            $payment->amount = $amountToPay;
        }

        $paymentCredentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();
        $commission = $paymentCredentials->offline_commission != null ? round(($booking->amount_to_pay / 100) * $paymentCredentials->offline_commission, 2) : 0;
        $value = Commission::where('company_id', $this->settings->id)->where('gateway', 'cash')->orWhere('gateway', 'card')->first();

        $totalAmount = $value ? $booking->amount_to_pay + $value->total_amount : $booking->amount_to_pay;
        $commissionAmount = $value ? $commission + $value->commission_amount : $commission;
        $pendingAmount = $value ? $commission + $value->pending_amount : $commission;

        if ($commissionAmount != 0 && $payment_status == 'completed' && ($payment->gateway = 'cash' || $payment->gateway = 'card')) {

            if (!is_null($value)) {
                $commission = Commission::findOrFail($value->id);
            }
            else{
                $commission = new Commission();
            }

            $commission->company_id        = $this->settings->id;
            $commission->currency_id       = $this->settings->currency_id;
            $commission->total_amount      = $totalAmount;
            $commission->commission_amount = $commissionAmount;
            $commission->pending_amount    = $pendingAmount;
            $commission->gateway           = $booking->payment_gateway;
            $commission->status            = 'pending';
            $commission->paid_on           = null;
            $commission->save();
        }

        $payment->save();
        return Reply::redirect(route('admin.bookings.index'), __('messages.updatedSuccessfully'));
    }

    public function addPayment(Request $request)
    {
        $totalPending    = $request->amountPending;
        $paidAmount      = $request->amountPaid;
        $id              = $request->bookingId;
        $total           = $request->total;
        $paymentGateway  = $request->paymentMode;

        $totalPending = intval(preg_replace('/[^\d. ]/', '', $totalPending));

        $pending = $totalPending - $paidAmount;

        $payment = new Payment();
        $payment->amount      = $total;
        $payment->status      = 'completed';
        $payment->gateway     = $paymentGateway;
        $payment->booking_id  = $id;
        $payment->currency_id = $this->settings->currency_id;

        if($pending == 0)
        {
            $booking = Booking::findOrFail($id);
            $booking->payment_status = 'completed';
            $booking->update();
        }
        else
        {
            $payment->status = 'pending';
        }

        $payment->amount_paid      = $paidAmount;
        $payment->amount_remaining = $pending;
        $payment->paid_on          = Carbon::now($this->settings->timezone)->setTimezone('UTC');
        $payment->transfer_status  = 'not_transferred';

        $payment->save();

        return Reply::redirect(route('admin.bookings.show', $id), __('messages.updatedSuccessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_booking'), 403);

        Booking::destroy($id);
        return Reply::redirect(route('admin.bookings.index'), __('messages.recordDeleted'));
    }

    public function download($id)
    {
        $this->booking = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'user' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'bookingPayments' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
        ])->find($id);

        abort_if($this->booking->status != 'completed', 403);
        /* @phpstan-ignore-next-line */
        $this->bookingPayments = $this->booking->bookingPayments;
        $this->totalPaid = $this->bookingPayments->sum('amount_paid');
        $this->totalPending = $this->booking->amount_to_pay - $this->totalPaid;
        $this->company = company();

        if ($this->user->is_admin || $this->user->is_employee || $this->booking->user_id == $this->user->id) {

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.booking.receipt', $this->data);
            $filename = __('app.receipt') . ' #' . $this->booking->id;
            return $pdf->download($filename . '.pdf');
        }
        else {
            abort(403);
        }
    }

    public function invocePdf($id)
    {
        $this->booking = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'user' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'bookingPayments' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
        ])->find($id);

        abort_if($this->booking->status != 'completed', 403);

        /* @phpstan-ignore-next-line */
        $this->bookingPayments = $this->booking->bookingPayments;
        $this->totalPaid = $this->bookingPayments->sum('amount_paid');
        $this->totalPending = $this->booking->amount_to_pay - $this->totalPaid;

        if ($this->user->is_admin || $this->user->is_employee || $this->booking->user_id == $this->user->id) {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.booking.receipt', $this->data);
            return $pdf->stream();
        }
        else {
            abort(403);
        }
    }

    public function print($id)
    {
        $this->id = $id;

        return view('admin.booking.print', $this->data);
    }

    public function requestCancel(Request $request, $id)
    {
        $booking = Booking::with('bookingPayments')->findOrFail($id);
        $booking->status = 'canceled';

        $booking->update();

        $bookingPayments = $booking->bookingPayments;
        $totalPaid = $bookingPayments->sum('amount_paid');

        $totalPending = $booking->amount_to_pay - $totalPaid;

        $commonCondition = $booking->payment_status == 'pending' && $booking->status != 'canceled' && $this->credentials->show_payment_options == 'show' && !$this->user->is_admin && !$this->user->is_employee;
        $current_url = ($request->current_url != null) ? $request->current_url : 'calendarPage';
        $view = view('admin.booking.show', compact('booking', 'commonCondition', 'current_url', 'totalPaid', 'bookingPayments', 'totalPending'))->render();

        $admins = User::allAdministrators()->get();
        $role = $this->user->is_admin == true && $this->user->is_employee == false ? 'Admin' : 'Customer';

        Notification::send($admins, new BookingCancel($booking, $role));

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function sendReminder()
    {
        $bookingId = \request('bookingId');
        $booking = Booking::findOrFail($bookingId);

        $customer = User::withoutGlobalScopes()->findOrFail($booking->user_id);

        $customer->notify(new BookingReminder($booking));

        return Reply::success(__('messages.bookingReminderSent'));
    }

    public function multiStatusUpdate(BookingStatusMultiUpdate $request)
    {

        foreach ($request->booking_checkboxes as $key => $booking_checkbox) {
            $booking = Booking::find($booking_checkbox);
            $booking->status = $request->change_status;
            $booking->save();
        }

        return Reply::dataOnly(['status' => 'success', '']);
    }

    public function updateCoupon(Request $request)
    {
        $couponId = $request->coupon_id;

        $tax = Tax::active()->first();

        $productAmount = $request->cart_services;

        if ($request->cart_discount > 0) {
            $totalDiscount = ($request->cart_discount / 100) * $productAmount;
            $productAmount -= $totalDiscount;
        }

        $percentAmount = ($tax->percent / 100) * $productAmount;

        $totalAmount   = ($productAmount + $percentAmount);

        $currentDate = Carbon::now()->format('Y-m-d H:i:s');

        $couponData = Coupon::where('coupons.start_date_time', '<=', $currentDate)
            ->where(function ($query) use ($currentDate) {
                $query->whereNull('coupons.end_date_time')
                    ->orWhere('coupons.end_date_time', '>=', $currentDate);
            })
            ->where('coupons.id', $couponId)
            ->where('coupons.status', 'active')
            ->first();

        if (!is_null($couponData) && $couponData->minimum_purchase_amount != 0 && $couponData->minimum_purchase_amount != null && $productAmount < $couponData->minimum_purchase_amount) {
            return Reply::errorWithoutMessage();
        }

        if (!is_null($couponData) && $couponData->used_time >= $couponData->uses_limit && $couponData->uses_limit != null && $couponData->uses_limit != 0) {
            return Reply::errorWithoutMessage();
        }

        if (!is_null($couponData)) {
            $days = json_decode($couponData->days);
            $currentDay = Carbon::now()->format('l');

            if (in_array($currentDay, $days)) {

                if (!is_null($couponData->percent) && $couponData->percent != 0) {
                    $percentAmnt = round(($couponData->percent / 100) * $totalAmount, 2);

                    if (!is_null($couponData->amount) && $percentAmnt >= $couponData->amount) {
                        $percentAmnt = $couponData->amount;
                    }

                    return Reply::dataOnly(['amount' => $percentAmnt, 'couponData' => $couponData]);
                }
                elseif (!is_null($couponData->amount) && (is_null($couponData->percent) || $couponData->percent == 0)) {
                    return Reply::dataOnly(['amount' => $couponData->amount, 'couponData' => $couponData]);
                }

            } else {
                return Reply::errorWithoutMessage();
            }
        }

        return Reply::errorWithoutMessage();
    }

    public function updateBookingDate(Request $request, $id)
    {
        abort_if(!$this->user->isAbleTo('update_booking'), 403);

        $booking = Booking::where('id', $id)->first();
        $booking->date_time   = Carbon::parse($request->startDate)->format('Y-m-d H:i:s');
        $booking->save();

        return Reply::successWithData('messages.updatedSuccessfully', ['status' => 'success']);
    }

    public function feedBack(Request $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_booking') && !$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_booking'), 403);

        $this->booking = Booking::with([
            'users' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'coupon' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'user' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'ratings' => function ($q) {
                $q->where('user_id', Auth::user()->id);
            },
        ])->find($id);

        $this->current_url = ($request->current_url != null) ? $request->current_url : 'calendarPage';
        $this->commonCondition = $this->booking->payment_status == 'pending' && $this->booking->status != 'canceled' && $this->credentials->show_payment_options == 'show' && !$this->user->is_admin && !$this->user->is_employee;

        $this->ratings = Rating::with([
            'service' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'deal' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'product' => function ($q) {
                $q->withoutGlobalScope(CompanyScope::class);
            },
        ])->where('user_id', Auth::user()->id)->where('booking_id', $id)->get();

        return view('admin.booking.feedback_modal', $this->data);

        /* if ($request->current_url == 'calendarPage') {
            return view('admin.booking.feedback_modal', $this->data);
        }

        $view = view('admin.booking.feedback_modal', $this->data)->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]); */
    }

    public function storeRating(Request $request)
    {
        $itemId = $request->itemId;
        $itemType = $request->itemType;
        $bookingId = $request->bookingId;
        $ratingValue = $request->ratingValue;
        $companyId = Booking::where('id', $bookingId)->first()->company_id;

        if ($request->ratingId == 'store') {

            foreach ($itemType as $key => $type)
            {
                $rating = new Rating();
                $rating->company_id = $companyId;
                $rating->booking_id = $bookingId;
                $rating->user_id = Auth::user()->id;

                if ($type == 'service') {
                    $rating->service_id = $itemId[$key];
                }

                if ($type == 'deal') {
                    $rating->deal_id = $itemId[$key];
                }

                if ($type == 'product') {
                    $rating->product_id = $itemId[$key];
                }

                $rating->rating = $ratingValue[$key];
                $rating->status = 'active';

                $rating->save();
            }
        }
        else {

            $rating = Rating::where('booking_id', $request->bookingId)->get();

            foreach ($rating as $ratings) {
                $ratings->delete();
            }

            foreach ($itemType as $key => $type)
            {
                $rating = new Rating();
                $rating->company_id = $companyId;
                $rating->booking_id = $bookingId;
                $rating->user_id = Auth::user()->id;

                if ($type == 'service') {
                    $rating->service_id = $itemId[$key];
                }

                if ($type == 'deal') {
                    $rating->deal_id = $itemId[$key];
                }

                if ($type == 'product') {
                    $rating->product_id = $itemId[$key];
                }

                $rating->rating = $ratingValue[$key];
                $rating->status = 'active';

                $rating->save();
            }

        }

        return Reply::dataOnly(['status' => 'success']);

    }

} /* end of class */
