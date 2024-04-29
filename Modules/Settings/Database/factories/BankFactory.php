<?php

namespace Modules\Settings\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Settings\Entities\Bank::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'bank_name' => $this->faker->unique()->randomElement(['Macquarie Bank', 'ANZ Bank', 'Commonwealth Bank', 'NAB Bank', 'Westpac Bank', 'Bankwest', 'Other']),
        ];
    }
}
