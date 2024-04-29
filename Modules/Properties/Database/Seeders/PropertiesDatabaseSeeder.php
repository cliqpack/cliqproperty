<?php

namespace Modules\Properties\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PropertiesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        // $this->call(PropertyTypeTableSeeder::class);
        // $this->call(PropertiesTableSeeder::class);
        // $this->call(PropertiesAddressTableSeeder::class);
        // $this->call(PropertyMemberTableSeeder::class);
        // $this->call(PropertiesLabelTableSeeder::class);

        // --------Enable After contact seeder run---------------//

        // $this->call(PropertyCheckoutKeyTableSeeder::class);
        // $this->call(PropertyActivityTableSeeder::class);
        // $this->call(PropertyActivityEmailTableSeeder::class);
        $this->call(PropertyRoomTableSeeder::class);
        $this->call(PropertyRoomAttributesTableSeeder::class);
        $this->call(PropertySalesAgreementTableSeeder::class);

        // --------Enable After contact seeder run---------------//
        
    }
}
