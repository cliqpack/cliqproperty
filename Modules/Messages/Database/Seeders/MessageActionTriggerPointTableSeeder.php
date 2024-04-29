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
            'Inspections' => ['Scheduled', 'Completed', 'Inspected', 'Routine'],
            'Maintenance' => ['Reported', 'Approved', 'Unapprove', 'Unquoted', 'Closed', 'Assigned', 'Finished', 'Unfinished'],
            'Listing' => ['Listing'],
            'Tenancy' => ['General arrears notice', 'Rent overdue reminder', 'Rent increase notice', 'Manual'],
            'Reminder' => ['Reminder'],
            'Routine' => ['Routine'],
            'Contact' => ['Manual']
        ];
    
        return $defaultTriggerPoints[$action] ?? [];
    }
    
}
