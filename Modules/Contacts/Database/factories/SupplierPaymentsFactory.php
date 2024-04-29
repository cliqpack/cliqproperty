<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\SupplierContact;

class SupplierPaymentsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\SupplierPayments::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'supplier_contact_id' => SupplierContact::inRandomOrder()->first()->id,
            'payment_method' => $this->faker->randomElement(['EFT', 'Cheque', 'BPay']),
            'payee' => $this->faker->numberBetween($min = 100000, $max = 300000),
            'bsb' => $this->faker->numberBetween($min = 100000, $max = 300000),
            'account_no' => $this->faker->bankAccountNumber,
            'split' => $this->faker->numberBetween($min = 1, $max = 100),
            'split_type' => $this->faker->randomElement(['%', 'null']),
        ];
    }
}
