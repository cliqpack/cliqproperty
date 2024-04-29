<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\OwnerContact;

class OwnerFolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerFolio::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'total_money' => $this->faker->numberBetween($min = 10000, $max = 30000),
            'balance' => $this->faker->numberBetween($min = 10000, $max = 30000),
            'regular_intervals' => $this->faker->numberBetween($min = 1, $max = 12),
            'next_disburse_date' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'withhold_amount' => $this->faker->numberBetween($min = 100, $max = 1000),
            'withold_reason' => $this->faker->randomElement(['Social Security', 'Medicare', ' Backup withholding']),
            'agreement_start' => $this->faker->dateTimeThisMonth($max = 'now', $timezone = null),
            'gained_reason' => $this->faker->word,
            'comment' => $this->faker->sentence,
            'agreement_end' => $this->faker->dateTimeThisYear($max = 'now', $timezone = null),
            'owner_access' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'owner_contact_id' => OwnerContact::inRandomOrder()->first()->id,

        ];
    }
}
