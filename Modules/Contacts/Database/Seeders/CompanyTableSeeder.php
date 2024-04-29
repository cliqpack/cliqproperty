<?php

namespace Modules\Contacts\Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class CompanyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Company::factory()->times(10)->create();

        // $this->call("OthersTableSeeder");
    }
}
