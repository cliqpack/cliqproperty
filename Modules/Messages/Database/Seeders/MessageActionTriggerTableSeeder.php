<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerTo;

class MessageActionTriggerTableSeeder extends Seeder
{
    public function run()
    {
        // Get unique company IDs from the 'companies' table
        $companyIds = DB::table('companies')->pluck('id')->toArray();

        foreach ($companyIds as $companyId) {
            $this->seedCompanyData($companyId);
        }
    }

    private function seedCompanyData($companyId)
    {
        // Get all action names for the given company
        $actionNames = MessageActionName::where('company_id', $companyId)->pluck('name', 'id');

        foreach ($actionNames as $actionId => $action) {
            // Get existing trigger points for the current action and company
            $existingTriggerPoints = MessageActionTriggerTo::where([
                'action_id' => $actionId,
                'company_id' => $companyId,
            ])->pluck('trigger_to')->toArray();

            // Combine existing trigger points with the predefined ones
            $allTriggerPoints = array_merge($existingTriggerPoints, $this->getDefaultTriggerPoints($action));

            // Ensure only unique trigger points
            $uniqueTriggerPoints = array_unique($allTriggerPoints);

            // Update the database with the unique trigger points
            MessageActionTriggerTo::where([
                'action_id' => $actionId,
                'company_id' => $companyId,
            ])->delete();

            foreach ($uniqueTriggerPoints as $triggerPoint) {
                MessageActionTriggerTo::create([
                    'action_id' => $actionId,
                    'trigger_to' => $triggerPoint,
                    'company_id' => $companyId,
                ]);
            }
        }
    }

    private function getDefaultTriggerPoints($action)
    {
        $defaultTriggerPoints = [
            'Contact' => ['Contact'],
            'Inspections All' => [
                'Owner',
                'Tenant'
            ],
            'Inspections Routine' => [
                'Owner',
                'Tenant'
            ],
            'Job' => [
                'Supplier',
                'Owner',
                'Tenant',
                'Agent',
            ],
            'Key Management' => ['Checked Out To'],
            'Lease Renewal' => [
                'Owner',
                'Tenant'
            ],
            'Owner Contact' => ['Owner'],
            'Reminders - Property' => [
                'Owner',
                'Tenant',
                'Supplier'
            ],
            'Rental Listing' => [
                'Tenant',
                'Owner'
            ],
            'Sale Listing' => [
                'Tenant',
                'Owner'
            ],
            'Sales Agreement' => [
                'Buyer',
                'Seller'
            ],
            'Task' => [
                'Contact',
                'Owner',
                'Tenant'
            ],
            'Tenancy' => [
                'Owner',
                'Tenant',
                'Strata Manager'
            ],
            'Tenant Invoice' => ['Tenant'],
            'Tenant Receipt' => ['Tenant'],
            'Tenant Rent Invoice' => ['Tenant'],
            'Tenant Statement' => ['Tenant'],
            'Folio Receipt' => ['Folio'],
            'Owner Financial Activity' => ['Owner'],
            'Owner Statement' => ['Owner'],
            'Supplier Statement' => ['Supplier']
        ];


        return $defaultTriggerPoints[$action] ?? [];
    }
}
