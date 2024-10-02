<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Role;
use App\User;
use App\Media;
use App\Module;
use App\Package;
use App\Currency;
use App\Language;
use Carbon\Carbon;
use App\Permission;
use App\SmsSetting;
use App\BookingTime;
use App\SmtpSetting;
use App\Helper\Files;
use App\Helper\Reply;
use App\GlobalSetting;
use App\ModuleSetting;
use GuzzleHttp\Client;
use App\Helper\Formats;
use App\PackageModules;
use App\SocialAuthSetting;
use App\Helper\Permissions;
use Illuminate\Http\Request;
use App\CurrencyFormatSetting;
use App\PaymentGatewayCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Http\Requests\Setting\UpdateNote;
use App\Http\Requests\Setting\UpdateTerms;
use App\Http\Requests\Setting\UpdateSetting;
use App\Http\Requests\Setting\UpdateGoogleApi;
use App\Http\Controllers\SuperAdminBaseController;
use App\Http\Requests\Superadmin\GoogleMap\UpdateRequest;
use App\ZoomSetting;

class SettingController extends SuperAdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('menu.settings'));
    }

    public function index()
    {
        abort_if(!$this->user->is_superadmin_employee, 403);

        $this->bookingTimes = BookingTime::all();
        $this->images = Media::select('id', 'image')->latest()->get();
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $this->dateFormats = Formats::dateFormats();
        $this->timeFormats = Formats::timeFormats();
        $this->dateObject = Carbon::now($this->settings->timezone);
        $this->currencies = Currency::all();
        $this->currencyFormatSetting = CurrencyFormatSetting::first();
        $this->enabledLanguages = Language::where('status', 'enabled')->orderBy('language_name')->get();
        $this->smtpSetting = SmtpSetting::first();
        $this->zoomSetting = ZoomSetting::first();
        $this->credentialSetting = PaymentGatewayCredentials::first();
        $this->smsSetting = SmsSetting::first();
        $this->roles = Role::whereNotIn('name', ['superadmin', 'administrator', 'employee', 'customer'])->whereNull('company_id')->get();
        $this->totalPermissions = Permission::count();
        $this->modules = Module::whereIn('name', Permissions::getModules($this->user->role))->get();
        $this->moduleSettings = ModuleSetting::where('status', 'deactive')->get();
        $this->socialCredentials = SocialAuthSetting::first();

        $client = new Client();
        $res = $client->request('GET', config('froiden_envato.updater_file_path'), ['verify' => false]);
        $this->lastVersion = $res->getBody();
        $this->lastVersion = json_decode($this->lastVersion, true);
        $currentVersion = File::get('version.txt');

        $description = $this->lastVersion['description'];

        $this->newUpdate = 0;

        if (version_compare($this->lastVersion['version'], $currentVersion) > 0)
        {
            $this->newUpdate = 1;
        }

        $this->updateInfo = $description;
        $this->lastVersion = $this->lastVersion['version'];

        $this->appVersion = File::get('version.txt');
        $laravel = app();
        $this->laravelVersion = $laravel::VERSION;

        $this->package_modules = PackageModules::get();
        $this->package = Package::trialPackage()->first();

        $arr = json_decode($this->package->package_modules, true);
        $selected_package_modules = [];

        if(!is_null($arr)) {

            foreach($arr as $value) {
                $selected_package_modules[] = $value;
            }
        }

        $this->selected_package_modules = $selected_package_modules;


        return view('superadmin.settings.index', $this->data);
    }

    public function editNote()
    {

        abort_if(!$this->user->roles()->withoutGlobalScopes()->first()->hasPermission('manage_settings'), 403);

        $this->setting = GlobalSetting::first();
        return view('superadmin.front-faq-settings.edit_note', $this->data);
    }

    // @codingStandardsIgnoreLine
    public function updateNote(UpdateNote $request, $id)
    {
        $setting = GlobalSetting::first();
        $setting->sign_up_note = $request->sign_up_note;
        $setting->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function editTerms()
    {

        abort_if(!$this->user->roles()->withoutGlobalScopes()->first()->hasPermission('manage_settings'), 403);

        $this->setting = GlobalSetting::first();
        return view('superadmin.front-faq-settings.edit_terms', $this->data);
    }

    // @codingStandardsIgnoreLine
    public function updateTerms(UpdateTerms $request, $id)
    {
        $setting = GlobalSetting::first();
        $setting->terms_note = $request->terms_note;
        $setting->save();

        return Reply::success(__('messages.updatedSuccessfully'));

    }

    // @codingStandardsIgnoreLine
    public function update(UpdateSetting $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->first()->hasPermission('manage_settings'), 403);

        $companyId = User::select('company_id')->where('id', Auth::user()->id)->first()->company_id;

        $setting = GlobalSetting::first();
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
            $setting->logo = Files::upload($request->logo, 'logo');
        }

        $setting->save();

        if ($setting->currency->currency_code !== 'INR') {
            $credential = PaymentGatewayCredentials::first();

            if ($credential->razorpay_status == 'active') {
                $credential->razorpay_status = 'deactive';

                $credential->save();
            }
        }

        cache()->forget('global_setting');
        session()->forget('myCurrencySymbol');

        // Update package curreny_id
        $this->updatePackageCurrencies($setting->currency_id);

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    protected function updatePackageCurrencies($currency_id)
    {
        DB::table('packages')->update(array('currency_id' => $currency_id));
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

    public function freeTrialSetting(Request $request)
    {
        $package = Package::find($request->id);
        $package->name = $request->name;
        $package->max_employees = $request->max_employees;
        $package->max_services = $request->max_services;
        $package->max_deals = $request->max_deals;
        $package->max_roles = $request->max_roles;
        $package->no_of_days = $request->no_of_days;
        $package->notify_before_days = $request->notify_before_days;
        $package->trial_message = $request->trial_message;
        $package->description = $request->description;
        $package->status = is_null($request->status) ? 'inactive' : $request->status;
        $package->package_modules = json_encode($request->package_modules);
        $package->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function editContactDetails(Request $request)
    {
        $globalSetting = GlobalSetting::first();
        $globalSetting->contact_email = $request->contact_email;
        $globalSetting->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function editMapKey(UpdateRequest $request)
    {
        $globalSetting = GlobalSetting::first();

        if ($request->map_option) {
            $globalSetting->map_option = $request->map_option;
        }
        else {
            $globalSetting->map_option = 'deactive';
        }

        $globalSetting->map_key = $request->map_key;
        $globalSetting->save();
        cache()->forget('global_setting');

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function saveGoogleCalendarConfig(UpdateGoogleApi $request)
    {
        $globalSetting = GlobalSetting::first();

        if ($request->google_calendar) {
            $globalSetting->google_calendar = $request->google_calendar;
        }
        else {
            $globalSetting->google_calendar = 'deactive';
        }

        $globalSetting->google_client_id = $request->google_client_id;
        $globalSetting->google_client_secret = $request->google_client_secret;
        $globalSetting->save();
        cache()->forget('global_setting');

        return Reply::success(__('messages.updatedSuccessfully'));
    }

}
