<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Database\factories\CountryFactory;
use Modules\Settings\Entities\Country;

class CountriesDatabaseSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seedData = [
            ['country_name' => 'Australia'],
            ['country_name' => 'Great Britain'],
            ['country_name' => 'New Zealand'],
            ['country_name' => 'Pacific'],
            ['country_name' => 'United States'],
        ];

        Model::unguard();


        foreach ($seedData as $data) {
            // Check if a country with the specified attributes exists
            $existingCountry = Country::where($data)->first();

            if (!$existingCountry) {
                // If it doesn't exist, create a new record
                Country::create($data);
            }
        }
    }
}
