<?php

use App\GlobalSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class GlobalSettingSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'company_name' => 'Froiden Technologies Pvt Ltd',
            'company_email' => 'company@example.com',
            'company_phone' => '1234512345',
            'contact_email' => 'froiden@example.com',
            'logo' => 'logo.png',
            'address' => 'Jaipur, India',
            'website' => 'http://www.xyz.com',
            'locale' => 'en',
            'sign_up_note' => 'Thank you for registration. Please verify your account via the verification link sent to your email',
            'terms_note' => 'By creating this account, I agree to Term and conditions',
            'timezone' => 'Asia/Kolkata',
            'currency_id' => '1'
        ];

        GlobalSetting::insert($data);

        $path = base_path('public/' . 'user-uploads' . '/');

        if (!File::isDirectory($path)) {
            File::makeDirectory($path);
        }

        $path1 = base_path('public/user-uploads/' . 'front-logo' . '/');

        if (!File::isDirectory($path1)) {
            File::makeDirectory($path1);
        }

        File::copy(public_path('front/images/logo.png'), public_path('user-uploads/front-logo/logo.png'));

        $path2 = base_path('public/user-uploads/' . 'logo' . '/');

        if (!File::isDirectory($path2)) {
            File::makeDirectory($path2);
        }

        File::copy(public_path('front/images/logo.png'), public_path('user-uploads/logo/logo.png'));
    }

}
