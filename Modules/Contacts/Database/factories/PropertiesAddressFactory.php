<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;

class PropertiesAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertiesAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'building_name' => $this->faker->secondaryAddress,
            'unit' => $this->faker->numberBetween($min = 1, $max = 50),
            'number' => $this->faker->buildingNumber,
            'street' => $this->faker->streetName,
            'suburb' => $this->faker->streetAddress,
            'postcode' => $this->faker->postcode,
            'state' => $this->faker->state,
            'country' => $this->faker->country,
        ];
    }
}
