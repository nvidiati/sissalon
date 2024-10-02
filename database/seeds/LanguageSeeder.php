<?php

use App\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::insert([
            [
                'language_code' => 'en',
                'language_name' => 'English',
                'status' => 'enabled',
            ],
            [
                'language_code' => 'es',
                'language_name' => 'Spanish',
                'status' => 'enabled',
            ],
        ]);
    }

}
