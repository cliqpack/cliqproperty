<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Messages\Entities\MessageActionName;

class MessageActionNameTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get unique company IDs from the 'companies' table
        $companyIds = DB::table('companies')->pluck('id')->toArray();

        // Action names to seed
        $names = [
            "Contact",
            "Inspections All",
            "Inspections Routine",
            "Job",
            "Key Management",
            "Lease Renewal",
            "Messages",
            "Owner Contact",
            "Reminders - Property",
            "Rental Listing",
            "Sale Listing",
            "Sales Agreement",
            "Task",
            "Tenancy",
            "Tenant Invoice",
            "Tenant Receipt",
            "Tenant Rent Invoice",
            "Tenant Statement",
            "Folio Receipt",
            "Owner Financial Activity",
            "Owner Statement",
            "Supplier Statement"
        ];

        // Loop through each company ID
        foreach ($companyIds as $companyId) {
            // Loop through each action name
            foreach ($names as $name) {
                // Check if the action name already exists for the company
                $existingActionName = MessageActionName::where('company_id', $companyId)
                    ->where('name', $name)
                    ->first();
                
                // If the action name does not exist, create it
                if (!$existingActionName) {
                    MessageActionName::create([
                        'name' => $name,
                        'company_id' => $companyId,
                    ]);
                }
            }
        }
    }
}
