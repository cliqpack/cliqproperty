<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\SupplierContact;

class SupplierDetailsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\SupplierDetails::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'supplier_contact_id' => SupplierContact::inRandomOrder()->first()->id,
            'abn' =>  $this->faker->numberBetween($min = 100000, $max = 300000),
            'website' => $this->faker->domainName,
            'account' => $this->faker->creditCardNumber,
            'priority' => $this->faker->randomElement(['High', 'normal', 'mendatory']),
            'auto_approve_bills' => $this->faker->boolean($chanceOfGettingTrue = 50),
        ];
    }
}
