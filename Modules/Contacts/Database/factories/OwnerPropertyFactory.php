<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Properties\Entities\Properties;

class OwnerPropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerProperty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_contact_id' => OwnerContact::inRandomOrder()->first()->id,
            'property_id' => Properties::inRandomOrder()->first()->id,
        ];
    }
}
