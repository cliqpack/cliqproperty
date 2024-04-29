<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettingListingProviderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companyIds = DB::table('companies')->pluck('id')->toArray();

        // Data to seed
        $data = [
            'name' => 'Rea',
            'agent_id' => 'LQNDVD',
            'is_available' => true,
            'is_enable' => false,
            'external_provider_type' => 'Example Type',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Seed the data for each company ID
        foreach ($companyIds as $companyId) {
            // Check if a record with the given name already exists for this company
            $existingRecord = DB::table('setting_listing_providers')->where(['name' => $data['name'], 'company_id' => $companyId])->first();

            if (!$existingRecord) {
                // Insert the record if it doesn't exist
                DB::table('setting_listing_providers')->insert(array_merge($data, ['company_id' => $companyId]));

                $this->command->info("Setting Listing Provider seeded successfully for company ID: $companyId");
            } else {
                $this->command->info("Setting Listing Provider with name '{$data['name']}' already exists for company ID: $companyId");
            }
        }

        // $this->call("OthersTableSeeder");
    }
}
