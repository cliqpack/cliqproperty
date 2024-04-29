<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;

class OwnerFeesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerFees::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fee_template' => $this->faker->randomElement(['Admin Fee ($)']),
            'income_account' => $this->faker->randomElement(['Administration fee (inc. tax)']),
            'fee_trigger' => $this->faker->randomElement(['Administration fee (inc. tax)']),
            'notes' => $this->faker->text(10),
            'amount' => $this->faker->numberBetween($min = 500, $max = 100000),
            'owner_contact_id'   => OwnerContact::inRandomOrder()->first()->id,
        ];
    }
}
