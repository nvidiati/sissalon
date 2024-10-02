<?php

namespace App\Observers;

use App\Role;
use App\Company;
use App\Package;
use Carbon\Carbon;
use App\VendorPage;
use App\BookingTime;
use App\ZoomSetting;
use App\Helper\Files;
use App\ThemeSetting;
use App\ModuleSetting;
use App\Helper\SearchLog;
use App\Helper\Permissions;
use App\PaymentGatewayCredentials;

class CompanyObserver
{

    public function saving(Company $company)
    {
        if ($company->isDirty('logo')) {
            Files::deleteFile($company->getOriginal('logo'), 'company-logo');
        }
    }

    public function creating(Company $company)
    {
        $user = auth()->user();

        if ($user && $user->is_superadmin) {

            $company->status = 'active';
            $company->verified = 'yes';
        }

        $company->slug = $company->company_name;

        if (is_null($company->package_id)) {
            $package = Package::active()->trial()->first();

            if (!is_null($package) && $package->no_of_days > 0) {
                $trial_ends_at = Carbon::now()->addDays($package->no_of_days);

                $company->package_id = $package->id;
                $company->trial_ends_at = $package->no_of_days ? Carbon::now()->addDays($package->no_of_days) : null;
            }
            else {
                $package = Package::active()->defaultPackage()->first();
                $company->package_id = $package->id;
            }
        }
    }

    public function created(Company $companySetting)
    {
        // seed booking times

        // seed admin settings
        $adminThemeSetting = ThemeSetting::ofAdminRole()->first();

        ThemeSetting::create([
            'company_id' => $companySetting->id,
            'role' => 'administrator',
            'primary_color' => $adminThemeSetting->primary_color,
            'secondary_color' => $adminThemeSetting->secondary_color,
            'sidebar_bg_color' => $adminThemeSetting->sidebar_bg_color,
            'sidebar_text_color' => $adminThemeSetting->sidebar_text_color,
            'topbar_text_color' => $adminThemeSetting->topbar_text_color
        ]);

        // seed payment settings
        PaymentGatewayCredentials::create([
            'company_id' => $companySetting->id,
        ]);

        // create roles and assign permissions
        $default_roles = array_diff( config('laratrust_seeder.default_roles'), ['superadmin','agent'] );

        foreach ($default_roles as $default_role) {
            $data = [
                'company_id' => $companySetting->id,
                'name' => $default_role,
                'display_name' => ucfirst($default_role),
                'description' => ucfirst($default_role),
            ];

            $role = Role::create($data);
            Permissions::assignPermissions($role);
        }

        /* package entries to module_settings table */
        $package = Package::find($companySetting->package_id);
        $arr = json_decode($package->package_modules, true);

        if (!is_null($arr)) {
            foreach ($arr as $module) {
                $admin_data = [
                    'company_id' => $companySetting->id,
                    'module_name' => $module,
                    'status' => 'active',
                    'type' => 'administrator',
                ];
                $employee_data = [
                    'company_id' => $companySetting->id,
                    'module_name' => $module,
                    'status' => 'deactive',
                    'type' => 'employee',
                ];
                ModuleSetting::create($admin_data);
                ModuleSetting::create($employee_data);
            }
        }

        VendorPage::create([
            'company_id' => $companySetting->id,
            'address' => $companySetting->address,
            'primary_contact' => $companySetting->company_phone,
        ]);
        SearchLog::createSearchEntry($companySetting->id, 'Company', $companySetting->company_name, 'superadmin.companies.edit');

        $zoomSetting = new ZoomSetting();
        $zoomSetting->api_key = null;
        $zoomSetting->company_id = $companySetting->id;
        $zoomSetting->secret_key = null;
        $zoomSetting->purchase_code = null;
        $zoomSetting->supported_until = null;
        $zoomSetting->meeting_app = 'in_app';
        $zoomSetting->enable_zoom = 'inactive';
        $zoomSetting->save();
    } /* end of created */

    public function updated(Company $companySetting)
    {
        if ($companySetting->isDirty('package_id')) {
            ModuleSetting::where('company_id', $companySetting->id)->delete();
            ModuleSetting::whereNull('company_id')->delete();
            $package = Package::findOrFail($companySetting->package_id);

            $arr = json_decode($package->package_modules, true);

            if (!is_null($arr)) {
                foreach ($arr as $module) {
                    $admin_data = [
                        'company_id' => $companySetting->id,
                        'module_name' => $module,
                        'status' => 'active',
                        'type' => 'administrator',
                    ];
                    $employee_data = [
                        'company_id' => $companySetting->id,
                        'module_name' => $module,
                        'status' => 'deactive',
                        'type' => 'employee',
                    ];
                    ModuleSetting::create($admin_data);
                    ModuleSetting::create($employee_data);
                }
            }
        }

        /* end of is_Dirty() */

        SearchLog::updateSearchEntry($companySetting->id, 'Company', $companySetting->company_name, 'superadmin.companies.edit');
    }

    /* end of updated */

    public function deleted(Company $companySetting)
    {
        SearchLog::deleteSearchEntry($companySetting->id, 'superadmin.companies.edit');
    }

}
/* end of class */
