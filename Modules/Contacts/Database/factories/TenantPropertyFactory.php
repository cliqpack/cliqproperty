<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;

class TenantPropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\TenantProperty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'tenant_contact_id' => TenantContact::inRandomOrder()->first()->id,
            'property_id' => Properties::inRandomOrder()->first()->id,
        ];
    }
}
