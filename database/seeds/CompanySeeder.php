<?php

use App\Role;
use App\User;
use App\Company;
use App\VendorPage;
use App\EmployeeGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CompanySeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::create(
        [
            'package_id' => 6,
            'company_name' => 'Cut & Style Salon',
            'company_email' => 'cutandstyle@example.com',
            'company_phone' => '1234512345',
            'address' => 'Jaipur, India',
            'date_format' => 'd-m-Y',
            'time_format' => 'h:i a',
            'licence_expire_on' => \Carbon\Carbon::now()->add(1, 'year')->format('Y-m-d'),
            'logo' => 'company.png',
            'website' => 'http://www.abc.com',
            'timezone' => 'Asia/Kolkata',
            'currency_id' => '1',
            'locale' => 'en',
            'status' => 'active',
            'verified' => 'yes',
            'popular_store' => '1'
        ]);

        $vendorPage = VendorPage::where('company_id', $company->id)->first();
        $vendorPage->description = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";
        $vendorPage->save();
        $path = base_path('public/' . 'user-uploads' . '/company-logo/');

        if (!File::isDirectory($path)) {
            File::makeDirectory($path);
        }

        File::copy(public_path('front/images/company.png'), public_path('user-uploads/company-logo/company.png'));

        $adminRole1 = Role::select('id', 'name')->where(['name' => 'administrator', 'company_id' => $company->id])->first();
        $employeeRole1 = Role::select('id', 'name')->where(['name' => 'employee', 'company_id' => $company->id])->first();

        // Insert admin
        $admin1 = User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => '123456',
            'company_id' => 1,
        ]);
        $admin1->attachRole($adminRole1->id);

        // Insert employees
        $employee1 = new User();
        $employee1->name = 'Malik Griffith';
        $employee1->email = 'malik@example.com';
        $employee1->password = '123456';
        $employee1->mobile = '1111';
        $employee1->company_id = $company->id;
        $employee1->save();

        $employee1->location()->sync('1');

        // add default employee role
        $employee1->attachRole($employeeRole1->id);
    }

}
