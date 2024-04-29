<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(CountriesDatabaseSeederTableSeeder::class);
        $this->call(BanksDatabaseSeederTableSeeder::class);
        $this->call(RegionDatabaseSeederTableSeeder::class);
        $this->call(FileFormatTableSeeder::class);
        $this->call(SettingListingProviderTableSeeder::class);

        // $this->call("OthersTableSeeder");
    }
}
