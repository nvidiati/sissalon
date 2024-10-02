<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Booking;
use Carbon\Carbon;
use App\Helper\Reply;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AdminBaseController;
use App\RoleUser;
use Illuminate\Support\Facades\DB;

class ShowDashboard extends AdminBaseController
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
            $startDate = Carbon::createFromFormat('Y-m-d', $request->startDate);
            $endDate = Carbon::createFromFormat('Y-m-d', $request->endDate);

            $totalBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate);

            if ($this->user->is_customer || $this->current_emp_role->name == 'customer') {
                $totalBooking = $totalBooking->where('user_id', $this->user->id);
            }

            if($this->current_emp_role->name == 'employee' && !session('loginRole')){
                $totalBooking = $totalBooking->where('user_id', $this->user->id);
            }

            $totalBooking = $totalBooking->count();

            $assignedPendingBooking = DB::table('bookings')->whereDate('date_time', '>=', $startDate)->whereDate('date_time', '<=', $endDate)
            ->where('status', 'pending')->join('booking_user', 'bookings.id', 'booking_user.booking_id')->where('booking_user.user_id', $this->user->id);

            $assignedPendingBooking = $assignedPendingBooking->count();

            if($this->current_emp_role->name == 'employee' || !session('loginRole')){
                $totalBooking = $totalBooking + $assignedPendingBooking;
            }

            $inProgressBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('status', 'in progress');

            if($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')){
                $inProgressBooking = $inProgressBooking->where('user_id', $this->user->id);
            }

            $inProgressBooking = $inProgressBooking->count();

            $pendingBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)->where('status', 'pending');

            if(($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')) || ($this->current_emp_role->name == 'employee')){
                $pendingBooking = $pendingBooking->where('user_id', $this->user->id);
            }

            $pendingBooking = $pendingBooking->count();

            $approvedBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('status', 'approved');

            if(($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')) || ($this->current_emp_role->name == 'employee')){
                $approvedBooking = $approvedBooking->where('user_id', $this->user->id);
            }
            $approvedBooking = $approvedBooking->count();

            $completedBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('status', 'completed');

            if(($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')) || ($this->current_emp_role->name == 'employee')){
                $completedBooking = $completedBooking->where('user_id', $this->user->id);
            }

            $completedBooking = $completedBooking->count();

            $canceledBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('status', 'canceled');

            if($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')){
                $canceledBooking = $canceledBooking->where('user_id', $this->user->id);
            }

            $canceledBooking = $canceledBooking->count();

            $offlineBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('source', 'pos');

            if($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')){
                $offlineBooking = $offlineBooking->where('user_id', $this->user->id);
            }

            $offlineBooking = $offlineBooking->count();

            $onlineBooking = Booking::whereDate('date_time', '>=', $startDate)
                ->whereDate('date_time', '<=', $endDate)
                ->where('source', 'online');

            if($this->current_emp_role->name != 'administrator' && !$this->user->isAbleTo('create_booking') || session('loginRole')){
                $onlineBooking = $onlineBooking->where('user_id', $this->user->id);
            }

            $onlineBooking = $onlineBooking->count();

            if($this->current_emp_role->name == 'administrator'){
                $totalCustomers = User::withoutGlobalScopes()->has('customerBookings')->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)->count();

                $totalEarnings = Booking::whereHas('completedPayment', function ($query) use ($startDate, $endDate) {
                    $query->whereDate('paid_on', '>=', $startDate)
                        ->whereDate('paid_on', '<=', $endDate);
                })->where('payment_status', 'completed')->sum('amount_to_pay');
            }
            else{
                $totalCustomers = 0;
                $totalEarnings = 0;
            }

            return Reply::dataOnly(['status' => 'success', 'totalBooking' => $totalBooking, 'assignedPendingBooking' => $assignedPendingBooking, 'pendingBooking' => $pendingBooking, 'approvedBooking' => $approvedBooking, 'inProgressBooking' => $inProgressBooking, 'completedBooking' => $completedBooking, 'canceledBooking' => $canceledBooking, 'offlineBooking' => $offlineBooking, 'onlineBooking' => $onlineBooking, 'totalCustomers' => $totalCustomers, 'totalEarnings' => round($totalEarnings, 2), 'user' => $this->user]);
        }

        if($this->user->is_admin){
            $recentSales = Booking::orderBy('id', 'desc')
                ->with(['user',
            'user' => function($q)
                {
                    $q->withoutGlobalScope(CompanyScope::class);
            }
            ])
            ->take(20)
            ->get();

        }
        else{
            $recentSales = null;
        }

        $todoItemsView = $this->generateTodoView();
        $users = User::has('location')->otherThanCustomers()->get();
        $isEmployeesHasLocation = ($users->count() == 0);
        return view('admin.dashboard.index', compact('recentSales', 'todoItemsView', 'isEmployeesHasLocation'));
    }

    public function roleLogin(Request $request)
    {
        if(request()->roleId){
            $role = RoleUser::where('user_id', $this->user->id)->where('role_id', request()->roleId)->first();

            if(!$role){
                $this->user->roles()->attach(request()->roleId);
            }
        }

        // @codingStandardsIgnoreLine
       \Session::put('loginRole', request()->roleId);

        return Reply::redirect(route('admin.dashboard'));
    }

    public function employeeLogin()
    {
        $roleId = \Session::get('loginRole');

        if($roleId){
            $role = RoleUser::where('user_id', $this->user->id)->where('role_id', $roleId)->first();

            if($role){
                $this->user->roles()->detach($roleId);
            }
        }

        session()->forget('loginRole');
        return Reply::redirect(route('admin.dashboard'));
    }

}
