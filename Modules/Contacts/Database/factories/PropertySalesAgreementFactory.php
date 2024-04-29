<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\BuyerContact;
use Modules\Contacts\Entities\SellerContact;
use Modules\Properties\Entities\Properties;

class PropertySalesAgreementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertySalesAgreement::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'seller_id' => SellerContact::inRandomOrder()->first()->id,
            'buyer_id' => BuyerContact::inRandomOrder()->first()->id,


            'has_buyer' => $this->faker->randomElement(['false']),
        ];
    }
}
