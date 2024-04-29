<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\OwnerContact;

class OwnerPropertyFeesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerPropertyFees::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fee_template' => $this->faker->randomElement(['Letting fee ($)', 'Management fee (%)']),
            'income_account' => $this->faker->randomElement(['Management fee (inc. tax) (%)', 'Letting fee ($)']),
            'fee_trigger' => $this->faker->randomElement(['First rent receipt', 'Rental receipt']),
            'notes' => $this->faker->text(10),
            'amount' => $this->faker->numberBetween($min = 500, $max = 100000),
            'owner_contact_id'   => OwnerContact::inRandomOrder()->first()->id,
        ];
    }
}
