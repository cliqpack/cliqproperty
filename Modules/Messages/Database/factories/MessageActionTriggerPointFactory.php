<?php

namespace Modules\Messages\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;

class MessageActionTriggerPointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Messages\Entities\MessageActionTriggerPoint::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {



        // $actionNames = MessageActionName::all();

        // // Create an associative array to map action names to their trigger points
        // $actionTriggerPoints = [
        //     'Inspections' => ['Scheduled', 'Completed', 'Inspected'],
        //     'Maintenance' => ['Reported', 'Approved', 'Unapprove', 'Unquoted', 'Closed', 'Assigned', 'Finished', 'Unfinished'],
        //     'Listing' => ['Listing'],
        //     'Tenancy' => ['General arrears notice', 'Rent overdue reminder', 'Rent increase notice', 'Manual'],
        //     // Add more actions as needed
        // ];

        // // Randomly select an action name
        // $actionName = $this->faker->randomElement(array_keys($actionTriggerPoints));
        // $triggerPoints = $actionTriggerPoints[$actionName];

        // // Shuffle the trigger points to ensure uniqueness
        // shuffle($triggerPoints);

        // // Retrieve the ID of the selected action
        // $action = $actionNames->firstWhere('name', $actionName);

        // return [
        //     'action_id' => $action->id, // Set the foreign key relationship
        //     'trigger_point' => array_shift($triggerPoints),
        //     'company_id' => $this->faker->numberBetween(1, 10), // Assuming there are 10 different company_ids (adjust as needed)
        // ];



        // $actions = [
        //     'Inspections' => ['Scheduled', 'Completed', 'Inspected'],
        //     'Maintenance' => ['Reported', 'Approved', 'Unapprove', 'Unquoted', 'Closed', 'Assigned', 'Finished', 'Unfinished'],
        //     'Listing' => ['Listing'],
        //     'Tenancy' => ['General arrears notice', 'Rent overdue reminder', 'Rent increase notice', 'Manual'],
        //     // Add more actions as needed
        // ];

        // // Randomly select an action name
        // $actionName = $this->faker->randomElement(array_keys($actions));
        // $triggerPoints = $actions[$actionName];
        // shuffle($triggerPoints);
        // // Retrieve the ID of the selected action
        // $actionId = MessageActionName::where('name', $actionName)->first()->id;

        // // Randomly shuffle the trigger points and select a subset based on the number of trigger points
        // $selectedTriggerPoint = array_shift($triggerPoints);
        // // $subsetTriggerPoints = $this->faker->randomElements($triggerPoints, count($triggerPoints));

        // return [
        //     'action_id' => $actionId, // Set the foreign key relationship
        //     'trigger_point' => $this->faker->randomElement($selectedTriggerPoint),
        //     'company_id' => $this->faker->numberBetween(1, 10),
        // ];
        // $companyCount = 10;

        // // Define the combinations of action_id and trigger_point for each action_id
        // $actionTriggerCombinations = [
        //     1 => ['Scheduled', 'Completed', 'Inspected'],
        //     2 => ['Reported', 'Approved', 'Unapprove', 'Unquoted', 'Closed', 'Assigned', 'Finished', 'Unfinished'],
        //     3 => ['Listing'],
        //     5 => ['General arrears notice', 'Rent overdue reminder', 'Rent increase notice', 'Manual'],
        // ];

        // // Loop through each company
        // for ($companyId = 1; $companyId <= $companyCount; $companyId++) {
        //     // Loop through each action_id
        //     foreach ($actionTriggerCombinations as $actionId => $triggerPoints) {
        //         // Create unique records for each action_id and trigger_point combination
        //         foreach ($triggerPoints as $triggerPoint) {
        //             MessageActionTriggerPoint::factory()
        //                 ->create([
        //                     'company_id' => $companyId,
        //                     'action_id' => $actionId,
        //                     'trigger_point' => $triggerPoint,
        //                 ]);
        //         }
        //     }
        // }
        // return [
        //     'action_id' => function () {
        //         // Generate a random action_id or select an existing one from the MessageActionName table
        //         return MessageActionName::inRandomOrder()->first()->id;
        //     },
        //     'action_name' => function () {
        //         // Generate a random action_id or select an existing one from the MessageActionName table
        //         return MessageActionName::inRandomOrder()->first()->name;
        //     },
        //     'trigger_point' => function (array $attributes) {
        //         $actionId = $attributes['action_id'];
        //         // $actionName = $attributes['action_name'];
        //         $triggerPoints = [];

        //         if ('action_name' === 'Inspections') {
        //             $triggerPoints = ['Scheduled', 'Completed', 'Inspected'];
        //         } elseif ('action_name' === 'Maintenance') {
        //             $triggerPoints = ['Reported', 'Approved', 'Unapprove', 'Unquoted', 'Closed', 'Assigned', 'Finished', 'Unfinished'];
        //         } elseif ('action_name' === 'Listing') {
        //             $triggerPoints = ['Listing'];
        //         } elseif ('action_name' === 'Tenancy') {
        //             $triggerPoints = ['General arrears notice', 'Rent overdue reminder', 'Rent increase notice', 'Manual'];
        //         }

        //         $uniqueTriggerPoint = null;
        //         $attempts = 0;

        //         while ($uniqueTriggerPoint === null && $attempts < 10000) {
        //             $potentialTriggerPoint = $this->faker->randomElement($triggerPoints);

        //             if (!MessageActionTriggerPoint::where('action_id', $actionId)
        //                 ->where('trigger_point', $potentialTriggerPoint)
        //                 ->exists()) {
        //                 $uniqueTriggerPoint = $potentialTriggerPoint;
        //             }

        //             $attempts++;
        //         }

        //         return $uniqueTriggerPoint;
        //     },
        //     'company_id' => $this->faker->numberBetween(1, 10), // Replace with your actual company_id range
        // ];
    }
}
