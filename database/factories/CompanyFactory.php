<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'company_name' => $this->faker->company,
            'address' => $this->faker->address,
            'phone' => $this->faker->e164PhoneNumber,
            'slug' => $this->faker->slug,
        ];
    }
}
