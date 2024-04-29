<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;

class PropertyRoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertyRoom::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'room' => $this->faker->randomElement(['Bedroom 1', 'Bedroom 2', 'Bedroom 3', 'Bathroom 1', 'Bathroom 2', 'Lounge room', 'Entrance/hall', 'Dining room', 'kitchen']),
            'delete_status' => $this->faker->randomElement(['false']),
            'sequence_no' => $this->faker->numberBetween($min = 1, $max = 12),
        ];
    }
}
