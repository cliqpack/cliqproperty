<?php

namespace Modules\Properties\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\PropertyType;

class PropertiesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\Properties::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [

            'reference' => $this->faker->address,
            'manager_id' => User::inRandomOrder()->first()->id,
            'property_type' => PropertyType::inRandomOrder()->first()->id,
            'description' => $this->faker->text(10),
            'location' => $this->faker->latitude.",".$this->faker->longitude,

            'bathroom' => $this->faker->numberBetween($min = 2, $max = 7),
            'bedroom' => $this->faker->numberBetween($min = 1, $max = 7),
            'car_space' => $this->faker->numberBetween($min = 1, $max = 2),
            'floor_area' => $this->faker->numberBetween($min = 1000, $max = 3000),
            'floor_size' => $this->faker->randomElement(['square', 'meter', 'cm']),
            'land_area' => $this->faker->numberBetween($min = 1000, $max = 3000),
            'land_size' => $this->faker->randomElement(['square', 'meter', 'cm']),
            'key_number' => $this->faker->numberBetween($min = 10, $max = 1000),
            'strata_manager_id' => $this->faker->randomDigit(1),
            'routine_inspections_frequency' => $this->faker->randomElement(['weekly', 'monthly']),
            'routine_inspections_frequency_type' => $this->faker->randomElement(['weekly', 'monthly']),
            'first_routine' => $this->faker->randomDigit(1),
            'first_routine_frequency_type' => $this->faker->randomElement(['weekly', 'monthly']),

            'routine_inspection_due_date' => $this->faker->dateTimeThisMonth($max = 'now', $timezone = null),
            'note' => $this->faker->text(10),
            'primary_type' => $this->faker->randomElement(['appartment', 'villa', 'house']),

            'company_id' => Company::inRandomOrder()->first()->id,
        ];
    }
}
