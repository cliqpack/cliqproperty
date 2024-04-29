<?php

namespace Modules\Tasks\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Inspection\Entities\Inspection;
use Modules\Listings\Entities\listing;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\Properties;
use Modules\Tasks\Entities\Task;

class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Tasks\Entities\Task::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'contact_id' => Contacts::inRandomOrder()->first()->id,
            'manager_id' => User::inRandomOrder()->first()->id,
            'company_id' => Company::inRandomOrder()->first()->id,
            // 'task_id' => Task::inRandomOrder()->first()->id,
            // 'inspection_id' => Inspection::inRandomOrder()->first()->id,
            // 'maintenance_id' => Maintenance::inRandomOrder()->first()->id,
            // 'listing_id' => listing::inRandomOrder()->first()->id,
            'due_by' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'summary' => $this->faker->randomElement(['new summary', 'pending   summary', 'On Going summary']),
            'description' => $this->faker->sentence($nbWords = 6, $variableNbWords = true),

            // 'comment' => $this->faker->sentence($nbWords = 6, $variableNbWords = true),

            // 'type' => $this->faker->randomElement(['important', 'easy']),
            'complete_date' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'status' => $this->faker->randomElement(['pending']),

        ];
    }
}
