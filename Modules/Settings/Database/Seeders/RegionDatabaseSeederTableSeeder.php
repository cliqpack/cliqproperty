<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Database\factories\RegionFactory;
use Modules\Settings\Entities\Region;

class RegionDatabaseSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seedData = [
            ['region' => 'New South Wales'],
            ['region' => 'Australian Capital Territory'],
            ['region' => 'Northern Territory'],
            ['region' => 'Queensland'],
            ['region' => 'South Australia'],
            ['region' => 'Tasmania'],
            ['region' => 'Victoria'],
            ['region' => 'Western Australia'],
        ];
        // Check if the seeding has already been done
        if (Region::count() > 0) {
            return;
        }

        Model::unguard();
        $count = 8;

        for ($i = 1; $i <= $count; $i++) {
            // Check if a region with specific attributes (e.g., name) exists
            $regionAttributes = [
                'region_name' => RegionFactory::new()->raw()['region_name'],
                // Add other attributes as needed
            ];

            Region::firstOrCreate($regionAttributes);
        }
    }
}
