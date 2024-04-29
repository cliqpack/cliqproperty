<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Messages\Database\Factories\MessageActionNameFactory;
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
        $names = ['Inspections', 'Maintenance', 'Listing', 'Tenancy', 'Contact', 'Reminder', 'Routine'];

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
