<?php

namespace App\Http\Controllers;

use App\Booking;
use App\BusinessService;
use App\Company;
use App\Deal;
use App\GlobalSetting;
use App\Helper\Formats;
use App\Location;
use App\ThemeSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use App\Language;
use App\ModuleSetting;
use App\Package;
use App\PaymentGatewayCredentials;
use App\Role;
use App\SmsSetting;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Country;
use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class AdminBaseController extends Controller
{
    public $user;
    public $pageTitle;
    public $settings;
    public $productsCount;
    public $adminCredentials;

    public function __construct()
    {
        parent::__construct();

        $this->smsSettings = SmsSetting::first();
        $this->languages = Language::where('status', 'enabled')->orderBy('language_name', 'asc')->get();
        $this->locations = Location::select('id', 'name')->get();
        $this->paymentCredential = PaymentGatewayCredentials::first();

        view()->share('smsSettings', $this->smsSettings);
        view()->share('languages', $this->languages);
        view()->share('locations', $this->locations);
        view()->share('paymentCredential', $this->paymentCredential);
        view()->share('calling_codes', $this->getCallingCodes());

        $this->middleware('auth')->only(['paymentGateway', 'offlinePayment', 'paymentConfirmation']);

        $this->middleware(function ($request, $next)
        {
            $this->themeSettings = ThemeSetting::first();
            $this->productsCount = request()->hasCookie('products') ? count(json_decode(request()->cookie('products'), true)) : 0;
            $this->user = auth()->user();

            $this->superadmin = GlobalSetting::first();

            if ($this->user) {
                $this->todoItems = $this->user->todoItems()->groupBy('status', 'position')->get();
                config(['froiden_envato.allow_users_id' => true]);

                if($this->user->hasRole('superadmin')){
                    $compId = Session::get('company_id');

                    if ($compId !== '' && $compId !== null) {
                        $companyId = Company::where('id', $compId)->first();
                        $companyId = company();
                    }
                }

                $this->settings = company();
            }

            if($this->user->hasRole('customer')){
                $this->settings = GlobalSetting::first();
            }

            if (!$this->settings) {
                return redirect(route('front.index'));
            }

            $this->user->hasRole('customer') ? config(['app.name' => $this->user->name]) : config(['app.name' => $this->settings->company_name]);
            config(['app.url' => url('/')]);

            App::setLocale($this->settings->locale);

            view()->share('superadmin', $this->superadmin);
            view()->share('user', $this->user);
            view()->share('settings', $this->settings);
            view()->share('themeSettings', $this->themeSettings);
            view()->share('productsCount', $this->productsCount);
            view()->share('date_picker_format', Formats::dateFormats()[$this->settings->date_format]);
            view()->share('date_format', Formats::datePickerFormats()[$this->settings->date_format]);
            view()->share('time_picker_format', Formats::timeFormats()[$this->settings->time_format]);

            $this->package = Package::find($this->settings->package_id);
            $this->total_employees = User::otherThanCustomers()->count();
            $this->total_deals = Deal::count();
            $this->total_business_services = BusinessService::count();
            $this->total_roles = Role::count();
            $this->customer_role = Role::withoutGlobalScopes()->where('name', 'customer')->first();

            if (\Session::get('loginRole')) {
                $this->current_emp_role = $this->user->roles()->withoutGlobalScopes()->where('id', \Session::get('loginRole'))->first();
            }
            else {
                $this->current_emp_role = $this->user->roles()->withoutGlobalScopes()->latest()->first();
            }

            if ($this->user->is_employee) {
                $this->original_emp_role = $this->user->roles()->withoutGlobalScopes()->where('name', 'employee')->first();
            }
            else if ($this->user->is_admin) {
                $this->original_emp_role = $this->user->roles()->withoutGlobalScopes()->where('name', 'administrator')->first();
            }
            else {
                $this->original_emp_role = $this->user->roles()->withoutGlobalScopes()->where('name', 'customer')->first();
            }

            $this->customer_bookings = $bookings = Booking::with([
                'user' => function ($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                }
            ])->where('user_id', $this->user->id)->count();

            view()->share('package_setting', $this->package);
            view()->share('total_employees', $this->total_employees);
            view()->share('total_deals', $this->total_deals);
            view()->share('total_business_services', $this->total_business_services);
            view()->share('total_roles', $this->total_roles);
            view()->share('customer_role', $this->customer_role);
            view()->share('current_emp_role', $this->current_emp_role);
            view()->share('original_emp_role', $this->original_emp_role);
            view()->share('customer_bookings', $this->customer_bookings);

            return $next($request);
        });
    }

    public function checkMigrateStatus()
    {
        return checkMigrateStatus();
    }

    public function getCallingCodes()
    {

        $codes = [];
        $location = Location::where('country_id', '!=', null)->pluck('country_id');

        $countries = count($location) > 0 ? Country::whereIn('id', $location)->get() : Country::get();

        foreach($countries as $country) {
            $codes = Arr::add($codes, $country->iso, array('name' => $country->name, 'dial_code' => '+'.$country->phonecode, 'code' => $country->iso));
        }

        return $codes;
    }

    public function generateTodoView()
    {
        $pendingTodos = $this->user->todoItems()->status('pending')->orderBy('position', 'DESC')->limit(5)->get();
        $completedTodos = $this->user->todoItems()->status('completed')->orderBy('position', 'DESC')->limit(5)->get();
        $dateFormat = $this->settings->date_format;

        $view = view('partials.todo_items_list', compact('pendingTodos', 'completedTodos', 'dateFormat'))->render();

        return $view;
    }

}
