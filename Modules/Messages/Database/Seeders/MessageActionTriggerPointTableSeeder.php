<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;

class MessageActionTriggerPointTableSeeder extends Seeder
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
            $existingTriggerPoints = MessageActionTriggerPoint::where([
                'action_id' => $actionId,
                'company_id' => $companyId,
            ])->pluck('trigger_point')->toArray();

            dump($existingTriggerPoints);

            // Combine existing trigger points with the predefined ones
            $allTriggerPoints = array_merge($existingTriggerPoints, $this->getDefaultTriggerPoints($action));

            // Ensure only unique trigger points
            $uniqueTriggerPoints = array_unique($allTriggerPoints);

            // Update the database with the unique trigger points
            MessageActionTriggerPoint::where([
                'action_id' => $actionId,
                'company_id' => $companyId,
            ])->delete();

            foreach ($uniqueTriggerPoints as $triggerPoint) {
                MessageActionTriggerPoint::create([
                    'action_id' => $actionId,
                    'trigger_point' => $triggerPoint,
                    'company_id' => $companyId,
                ]);
            }
        }
    }

    private function getDefaultTriggerPoints($action)
    {
        $defaultTriggerPoints = [
            'Contact' => ['Manual'],
            'Inspections All' => [
                'Manual',
                'Scheduled',
                'Rescheduled',
                'Shared with owner',
                'Shared with tenant',
                'Assigned to tenant',
                'Returned by tenant',
                'Closed'
            ],
            'Inspections Routine' => [
                'Manual',
                'Scheduled',
                'Rescheduled',
                'Shared with owner',
                'Shared with tenant',
                'Closed'
            ],
            'Job' => [
                'Manual',
                'Pending',
                'Reported',
                'Rejected',
                'Quoted',
                'Assigned',
                'Finished',
                'Completed',
                'Approved',
                'Unapprove',
                'Unquoted'
            ],
            'Key Management' => ['Manual'],
            'Lease Renewal' => ['Manual'],
            'Owner Contact' => ['Manual'],
            'Reminders - Property' => [
                'Manual',
                'Report'
            ],
            'Rental Listing' => [
                'Manual',
                'Created',
                'Published',
                'Leased',
                'Closed'
            ],
            'Sale Listing' => [
                'Manual',
                'Created',
                'Published',
                'Leased',
                'Closed'
            ],
            'Sales Agreement' => [
                'Manual',
                'Contracted',
                'Listed'
            ],
            'Task' => [
                'Created',
                'Manual'
            ],
            'Tenancy' => [
                'Manual',
                'Created',
                'Rent Adjustment'
            ],
            'Tenant Invoice' => [
                'Manual',
                'Created'
            ],
            'Tenant Receipt' => ['Receipted'],
            'Tenant Rent Invoice' => [
                'Manual',
                'Created'
            ],
            'Tenant Statement' => ['Disbursed'],
            'Folio Receipt' => ['Receipted'],
            'Owner Financial Activity' => ['Created'],
            'Owner Statement' => ['Disbursed'],
            'Supplier Statement' => ['Disbursed']
        ];


        return $defaultTriggerPoints[$action] ?? [];
    }

}
