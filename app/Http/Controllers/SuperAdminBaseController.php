<?php

namespace App\Http\Controllers;

use App\Page;
use App\Company;
use App\Country;
use App\Language;
use App\Location;
use App\SmsSetting;
use App\GlobalSetting;
use App\Helper\Formats;
use App\FrontThemeSetting;
use Illuminate\Support\Arr;
use App\GoogleCaptchaSetting;
use App\PaymentGatewayCredentials;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SuperAdminBaseController extends Controller
{
    public $user;
    public $pageTitle;
    public $settings;
    public $activeCompanies;
    public $deActiveActiveCompanies;
    public $paymentGatewayCredential;

    public function __construct()
    {
        parent::__construct();

        $this->smsSettings = SmsSetting::first();
        $this->global = $this->settings = GlobalSetting::first();
        $this->activeCompanies = Company::where('status', 'active')->count();
        $this->deActiveCompanies = Company::where('status', 'deactive')->count();

        $this->googleCaptchaSettings = GoogleCaptchaSetting::first();

        $this->languages = Language::where('status', 'enabled')->orderBy('language_name', 'asc')->get();
        $this->frontThemeSettings = FrontThemeSetting::first();
        $this->paymentGatewayCredential = PaymentGatewayCredentials::first();
        $this->pages = Page::all();

        view()->share('activeCompanies', $this->activeCompanies);
        view()->share('deActiveCompanies', $this->deActiveCompanies);
        view()->share('credential', $this->paymentGatewayCredential);
        view()->share('smsSettings', $this->smsSettings);
        view()->share('googleCaptchaSettings', $this->googleCaptchaSettings);
        view()->share('languages', $this->languages);
        view()->share('frontThemeSettings', $this->frontThemeSettings);
        view()->share('pages', $this->pages);
        view()->share('calling_codes', $this->getCallingCodes());

        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();

            if ($this->user) {
                config(['froiden_envato.allow_users_id' => true]);
            }

            if (!Auth::user()->is_superadmin && !Auth::user()->is_agent) {
                return redirect(route('front.index'));
            }

            config(['app.name' => $this->settings->company_name]);
            config(['app.url' => url('/')]);

            App::setLocale($this->settings->locale);

            view()->share('user', $this->user);
            view()->share('settings', $this->settings);
            view()->share('date_picker_format', Formats::dateFormats()[$this->settings->date_format]);
            view()->share('date_format', Formats::datePickerFormats()[$this->settings->date_format]);
            view()->share('time_picker_format', Formats::timeFormats()[$this->settings->time_format]);

            return $next($request);
        });
    }

    public function getCallingCodes()
    {
        $codes = [];
        $location = Location::where('country_id', '!=', null)->pluck('country_id');
        $countries = count($location) > 0 ? Country::whereIn('id', $location)->get() : Country::get();

        foreach($countries as $country) {
            $codes = Arr::add($codes, $country->iso, array('name' => $country->name, 'dial_code' => '+'.$country->phonecode));
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
