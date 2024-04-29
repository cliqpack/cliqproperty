<?php

namespace Modules\Maintenance\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Maintenance\Entities\Maintenance;

class MaintenanceLabelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Maintenance\Entities\MaintenanceLabel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'job_id' => Maintenance::inRandomOrder()->first()->id,
            'labels' => $this->faker->randomElement(['Commercial', 'Important']),
        ];
    }
}
