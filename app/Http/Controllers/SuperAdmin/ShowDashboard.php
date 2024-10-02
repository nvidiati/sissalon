<?php

namespace App\Http\Controllers\SuperAdmin;

use App\User;
use App\Booking;
use App\Category;
use App\Company;
use Carbon\Carbon;
use Froiden\Envato\Helpers\Reply;
use App\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\SuperAdminBaseController;
use App\Location;
use App\Payment;
use Carbon\CarbonPeriod;

class ShowDashboard extends SuperAdminBaseController
{

    public function __construct()
    {

        parent::__construct();
        view()->share('pageTitle', __('menu.dashboard'));
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if(\request()->ajax())
        {
            $startDate = Carbon::createFromFormat($this->settings->date_format, $request->startDate)->format('Y-m-d');
            $endDate = Carbon::createFromFormat($this->settings->date_format, $request->endDate)->format('Y-m-d');

            $totalCustomers = User::withoutGlobalScopes()
                ->with('customerBookings')->has('customerBookings')
                ->whereHas('customerBookings', function ($query) use ($startDate,$endDate) {
                    $query->whereDate('date_time', '>=', Carbon::createFromFormat('Y-m-d', $startDate))
                        ->whereDate('date_time', '<=', Carbon::createFromFormat('Y-m-d', $endDate));
                })
                ->count();

            $totalVendors = User::withoutGlobalScopes()->allAdministrators()
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            $totalEarnings = Booking::withoutGlobalScopes()->whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('payment_status', 'completed')
                ->sum('amount_to_pay');

            $totalCommission = Payment::withoutGlobalScopes()
                ->where('status', 'completed')->whereNotNull('paid_on')
                ->whereDate('paid_on', '>=', $startDate)
                ->whereDate('paid_on', '<=', $endDate)
                ->sum('commission');

            $activeCompanies = Company::where('status', '=', 'active')->count();

            $deActiveCompanies = Company::where('status', '=', 'inactive')->count();

            return Reply::dataOnly(['status' => 'success', 'totalCustomers' => $totalCustomers, 'totalEarnings' => round($totalEarnings, 2), 'totalCommission' => round($totalCommission, 2), 'totalVendors' => $totalVendors, 'activeCompanies' => $activeCompanies, 'deActiveCompanies' => $deActiveCompanies,]);
        }


        $this->totalCategories = Category::withoutGlobalScopes()->count();
        $this->todoItemsView = $this->generateTodoView();
        $this->isNotSetExchangeRate = (Currency::where('exchange_rate', null)->where('deleted_at', null)->count() > 0);
        $this->isNotSetCountry = (Location::where('country_id', null)->where('timezone_id', null)->count() > 0);
        $this->isNotSetLongitude = (Location::where('lat', 0)->where('lng', 0)->count() > 0);

        return view('superadmin.dashboard.index', $this->data);
    }

}
