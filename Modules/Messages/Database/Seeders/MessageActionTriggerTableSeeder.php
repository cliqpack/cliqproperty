<?php

namespace Modules\Messages\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
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
            'Inspections' => ['Owner', 'Tenant'],
            'Maintenance' => ['Owner', 'Tenant', 'Supplier'],
            'Listing' => ['Owner', 'Tenant'],
            'Tenancy' => ['Owner', 'Tenant'],
            'Reminder' => ['Owner', 'Tenant', 'Supplier'],
            'Routine' => ['Owner', 'Tenant'],
            'Contact'=>['Owner', 'Tenant', 'Supplier']
        ];
    
        return $defaultTriggerPoints[$action] ?? [];
    }
}
