<?php

namespace Modules\Contacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Contacts\Database\Seeders\InspectionTableSeeder as SeedersInspectionTableSeeder;
use Modules\Inspection\Database\Seeders\InspectionTableSeeder;

class ContactsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        //Start Contacts
        $this->call(CompanyTableSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(PropertyTypeTableSeeder::class);
        $this->call(PropertiesTableSeeder::class);
        $this->call(ContactTableSeeder::class);
        $this->call(OwnerContactTableSeeder::class);
        $this->call(TenantContactTableSeeder::class);
        $this->call(SupplierContactTableSeeder::class);
        $this->call(ContactPhysicalAddressTableSeeder::class);
        $this->call(ContactPostalAddressTableSeeder::class);
        $this->call(SupplierDetailsTableSeeder::class);
        $this->call(OwnerFolioTableSeeder::class);
        $this->call(ContactCommunicationTableSeeder::class);
        $this->call(SupplierPaymentsTableSeeder::class);
        $this->call(OwnerFeesTableSeeder::class);
        $this->call(OwnerPropertyFeesTableSeeder::class);
        $this->call(TenantFolioTableSeeder::class);
        $this->call(OwnerPropertyTableSeeder::class);
        $this->call(TenantPropertyTableSeeder::class);
        $this->call(ContactLabelTableSeeder::class);
        $this->call(OwnerPaymentTableSeeder::class);


        //End Contacts
        //Start Properties


        $this->call(PropertiesAddressTableSeeder::class);
        $this->call(PropertyMemberTableSeeder::class);
        $this->call(PropertiesLabelTableSeeder::class);

        // --------Enable After contact seeder run---------------//

        $this->call(PropertyCheckoutKeyTableSeeder::class);


        $this->call(PropertyRoomTableSeeder::class);
        $this->call(PropertyRoomAttributesTableSeeder::class);


        // --------Enable After contact seeder run---------------//


        //End Properties

        //Inspection start
        $this->call(InspectionTableSeeder::class);

        //End Inspection
        //Listing start
        $this->call(ListingTableSeeder::class);


        //End Listing
        //Maintenance start
        $this->call(MaintenanceTableSeeder::class);
        $this->call(MaintenanceLabelTableSeeder::class);


        //End Maintenance
        //Task start
        $this->call(TaskTableSeeder::class);
        $this->call(TaskLabelTableSeeder::class);


        //End Task

        $this->call(ContactActivityTableSeeder::class);
        $this->call(PropertyActivityTableSeeder::class);
        $this->call(PropertyActivityEmailTableSeeder::class);

        //problem ase need buyer and seller
        // $this->call(PropertySalesAgreementTableSeeder::class);
    }
}
