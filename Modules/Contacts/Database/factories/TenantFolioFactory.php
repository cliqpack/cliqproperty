<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;

class TenantFolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\TenantFolio::class;

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
            'rent' => $this->faker->numberBetween($min = 500, $max = 30000),
            'rent_type' => $this->faker->randomElement(['Weekly', 'Monthly', 'Per day']),
            'rent_includes_tax' => $this->faker->numberBetween($min = 0, $max = 20),
            'bond_required' => $this->faker->numberBetween($min = 100, $max = 1000),
            'bond_held' => $this->faker->numberBetween($min = 100, $max = 1000),
            'move_in' => $this->faker->dateTimeThisMonth($max = 'now', $timezone = null),
            'move_out' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'agreement_start' => $this->faker->dateTimeThisMonth($max = 'now', $timezone = null),
            'agreement_end' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),

            'periodic_tenancy' => $this->faker->boolean($chanceOfGettingTrue = 50),

            'paid_to' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'part_paid' => $this->faker->numberBetween($min = 100, $max = 1000),
            'invoice_days_in_advance' => $this->faker->numberBetween($min = 1, $max = 15),
            'rent_review_frequency' => $this->faker->numberBetween($min = 50, $max = 200),

            'next_rent_review' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'exclude_form_arrears' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'bank_reterence' => $this->faker->randomElement(['Bank reference']),
            'receipt_warning' => $this->faker->randomElement(['Receipt warning']),
            'tenant_access' => $this->faker->boolean($chanceOfGettingTrue = 50),

        ];
    }
}
