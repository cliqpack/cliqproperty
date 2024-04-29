<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\OwnerContact;

class OwnerPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_contact_id' => OwnerContact::inRandomOrder()->first()->id,
            'method' => $this->faker->randomElement(['EFT', 'Cheque', 'BPay']),
            'payee' => $this->faker->creditCardType,
            'bsb' => $this->faker->numberBetween($min = 100000, $max = 300000),
            'account' => $this->faker->bankAccountNumber,
            'split' => $this->faker->numberBetween($min = 1, $max = 100),
            'split_type' => $this->faker->randomElement(['%', 'null']),
        ];
    }
}
