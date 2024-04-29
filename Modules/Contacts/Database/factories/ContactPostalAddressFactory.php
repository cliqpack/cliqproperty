<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;

class ContactPostalAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\ContactPostalAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contacts::inRandomOrder()->first()->id,
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
