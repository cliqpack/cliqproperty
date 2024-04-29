<?php

namespace Modules\Properties\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Properties\Entities\Properties;

class PropertyCheckoutKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertyCheckoutKey::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contacts::inRandomOrder()->first()->id,
            'property_id' => Properties::inRandomOrder()->first()->id,
            'return_due' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'return_time' => $this->faker->time($format = 'H:i:s', $max = 'now'),
            'note' => $this->faker->text(10),
            'check_type' => $this->faker->randomElement(['check-in', 'check-out']),
        ];
    }
}
