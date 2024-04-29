<?php

namespace Modules\Inspection\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;

class InspectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Inspection\Entities\Inspection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'inspection_type' => $this->faker->randomElement(['Entry', 'Exit', 'Routine']),
            'inspection_date' => $this->faker->dateTimeThisMonth($max = 'now', $timezone = null),
            'start_time' => $this->faker->time($format = 'H:i:s', $max = 'now'),
            'end_time' => $this->faker->time($format = 'H:i:s', $max = 'now'),
            'duration' => $this->faker->randomElement(['60']),
            'summery' => $this->faker->text(10),
            'manager_id' => User::inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['init', 'complete']),
            'level' => $this->faker->randomElement(['init', 'complete']),
            'company_id' => Company::inRandomOrder()->first()->id,
        ];
    }
}
