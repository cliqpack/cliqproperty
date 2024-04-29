<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;

class ContactLabelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\ContactLabel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contacts::inRandomOrder()->first()->id,
            'labels' => $this->faker->randomElement(['Commercial', 'Important']),

        ];
    }
}
