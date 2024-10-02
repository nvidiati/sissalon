<?php

namespace App\Http\Controllers\Admin;

use App\Tax;
use App\Role;
use App\User;
use App\Media;
use App\Module;
use App\Company;
use App\Currency;
use App\Language;
use Carbon\Carbon;
use App\Permission;
use App\SmsSetting;
use App\VendorPage;
use App\BookingTime;
use App\BusinessService;
use App\OfficeLeave;
use App\SmtpSetting;
use App\Helper\Files;
use App\Helper\Reply;
use App\ModuleSetting;
use GuzzleHttp\Client;
use App\Helper\Formats;
use App\Helper\Permissions;
use Illuminate\Http\Request;
use App\GatewayAccountDetail;
use App\PaymentGatewayCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Http\Requests\Setting\UpdateSetting;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\Admin\Company\BookingSetting;
use App\ZoomSetting;

class SettingController extends AdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('menu.settings'));
    }

    public function index()
    {
        $this->bookingTimes = BookingTime::all();

        $this->serviceLocations = BookingTime::with(['locations' => function($q){
            $q->with('timezone');
        }])->get()->unique('location_id');

        $uniqueLocations = $this->serviceLocations->pluck('location_id')->toArray();
        $this->serviceAtLocation = BusinessService::whereIn('location_id', $uniqueLocations)->get()->countBy('location_id')->toArray();

        $this->images = Media::select('id', 'image')->latest()->get();
        $this->tax = Tax::active()->first();
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $this->dateFormats = Formats::dateFormats();
        $this->timeFormats = Formats::timeFormats();
        $this->dateObject = Carbon::now($this->settings->timezone);
        $this->currencies = Currency::all();
        $this->enabledLanguages = Language::where('status', 'enabled')->orderBy('language_name')->get();
        $this->smtpSetting = SmtpSetting::first();
        $this->credentialSetting = PaymentGatewayCredentials::withoutGlobalScopes(['company'])->first();
        $this->smsSetting = SmsSetting::first();
        $this->zoomSetting = ZoomSetting::where('company_id', $this->user->company_id)->first();
        $this->roles = Role::whereNotIn('name', ['superadmin', 'administrator', 'agent'])->where('company_id', $this->user->company_id)->get();
        $this->totalPermissions = Permission::count();
        $this->totalPermission = Permission::whereIn('module_id', Module::whereIn('name', Permissions::getModules($this->user->role))->pluck('id'))->count();
        $this->modules = Module::whereIn('name', Permissions::getModules($this->user->role))->get();
        $this->moduleSettings = ModuleSetting::where('status', 'deactive')->get();
        $employees = User::AllEmployees()->get();

        $client = new Client();
        $res = $client->request('GET', config('froiden_envato.updater_file_path'), ['verify' => false]);
        $this->lastVersion = $res->getBody();
        $this->lastVersion = json_decode($this->lastVersion, true);
        $currentVersion = File::get('version.txt');

        $description = $this->lastVersion['description'];

        $this->newUpdate = 0;

        if (version_compare($this->lastVersion['version'], $currentVersion) > 0) {
            $this->newUpdate = 1;
        }

        $this->updateInfo = $description;
        $this->lastVersion = $this->lastVersion['version'];

        $this->appVersion = File::get('version.txt');
        $laravel = app();
        $this->laravelVersion = $laravel::VERSION;
        $this->officeLeaves = OfficeLeave::all();

        $this->stripePaymentSetting = GatewayAccountDetail::ofStatus('active')->ofGateway('stripe')->first();
        $this->razoypayPaymentSetting = GatewayAccountDetail::ofStatus('active')->ofGateway('razorpay')->first();
        $this->paypalPaymentSetting = GatewayAccountDetail::ofGateway('paypal')->ofGateway('paypal')->first();

        $this->vendorPage = VendorPage::first();

        if (!$this->user->is_customer) {
            $this->companyBookingNotification = company()->bookingNotification;
        }

        return view('admin.settings.index', $this->data);
    }

    // @codingStandardsIgnoreLine
    public function update(UpdateSetting $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('manage_settings'), 403);
        $company = User::with('company')->where('id', auth()->user()->id)->first();

        $setting = Company::findOrFail($company->company->id);
        $setting->company_name = $request->company_name;
        $setting->company_email = $request->company_email;
        $setting->company_phone = $request->company_phone;
        $setting->address = $request->address;
        $setting->date_format = $request->date_format;
        $setting->time_format = $request->time_format;
        $setting->website = $request->website;
        $setting->timezone = $request->timezone;
        $setting->locale = $request->input('locale');
        $setting->currency_id = $request->currency_id;

        if ($request->hasFile('logo')) {
            $setting->logo = Files::upload($request->logo, 'company-logo');
        }

        $setting->save();

        if ($setting->currency->currency_code !== 'INR') {
            $credential = PaymentGatewayCredentials::first();

            if ($credential->razorpay_status == 'active') {
                $credential->razorpay_status = 'deactive';

                $credential->save();
            }
        }

        session()->forget('myCurrencySymbol');

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function changeLanguage($code)
    {
        $language = Language::where('language_code', $code)->first();

        if ($language) {
            $this->settings->locale = $code;
        }
        else if ($code == 'en') {
            $this->settings->locale = 'en';
        }

        $this->settings->save();
        return Reply::success(__('messages.languageChangedSuccessfully'));
    }

    public function saveBookingTimesField(BookingSetting $request)
    {
        $company = User::with('company')->where('id', auth()->user()->id)->first();

        $setting = Company::findOrFail($company->company->id);
        $setting->booking_per_day = $request->no_of_booking_per_customer;
        $setting->multi_task_user = $request->multi_task_user;
        $setting->employee_selection = $request->employee_selection;
        $setting->disable_slot = $request->disable_slot;
        $setting->booking_time_type = $request->booking_time_type;
        $setting->cron_status = $request->cron_status;
        $setting->display_deal = $request->display_deal;
        $setting->approve_online_booking = $request->approve_online;
        $setting->approve_offline_booking = $request->approve_offline;

        if (!$request->cron_status) {
            $setting->cron_status = 'deactive';
        }

        if (!$request->display_deal) {
            $setting->display_deal = 'inactive';
        }

        if (!$request->approve_online) {
            $setting->approve_online_booking = 'inactive';
        }

        if (!$request->approve_offline) {
            $setting->approve_offline_booking = 'inactive';
        }

        $setting->duration = $request->duration;
        $setting->duration_type = $request->duration_type;
        $setting->save();

        if ($request->disable_slot == 'enabled') {
            DB::table('payment_gateway_credentials')->where('id', 1)->update(['show_payment_options' => 'hide', 'offline_payment' => 1]);
        }

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function moduleSetting()
    {
        $package_modules = json_decode($this->package->package_modules, true) ?: [];

        $admin_modules = ModuleSetting::select('module_name')->where(['type' => 'administrator', 'status' => 'active'])->get()->map(function ($item, $key) {return $item->module_name;
        })->toArray();

        $employee_modules = ModuleSetting::select('module_name')->where(['type' => 'employee', 'status' => 'active'])->get()->map(function ($item, $key) {return $item->module_name;
        })->toArray();

        return view('admin.settings.module-settings', compact('package_modules', 'admin_modules', 'employee_modules'));
    }

    public function updateModuleSetting(Request $request)
    {
        $company = User::with('company')->where('id', auth()->user()->id)->first();

        ModuleSetting::where(['company_id' => $company->company->id, 'module_name' => $request->module_name, 'type' => $request->user_type])
            ->update(['status' => $request->status]);

        return Reply::success(__('messages.updatedSuccessfully'));
    }

} /* end of class */
