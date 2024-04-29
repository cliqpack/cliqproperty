<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\PropertyActivity;

class PropertyActivityEmailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertyActivityEmail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email_to' => $this->faker->companyEmail,
            'email_from' => $this->faker->companyEmail,
            'subject' => $this->faker->realText($maxNbChars = 200, $indexSize = 2),
            'email_body' => $this->faker->realText($maxNbChars = 200, $indexSize = 2),
            'email_status' => $this->faker->randomElement(['success']),
            'property_activity_id' => PropertyActivity::inRandomOrder()->first()->id,
        ];
    }
}
