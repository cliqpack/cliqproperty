<?php

namespace Modules\Settings\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Settings\Entities\Country::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'country_name' => $this->faker->unique()->randomElement(['Australia', 'Great Britain', 'New Zealand', 'Pacific', 'United States']),
        ];
    }
}
