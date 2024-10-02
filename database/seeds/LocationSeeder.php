<?php

use Illuminate\Database\Seeder;
use App\Location;

class LocationSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $locations = [
            [
                'name' => 'Jaipur, India',
                'country_id' => 99,
                'timezone_id' => 189,
                'status' => 'active',
            ],
            [
                'name' => 'New York, USA',
                'country_id' => 226,
                'timezone_id' => 404,
                'status' => 'active',
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }

}
