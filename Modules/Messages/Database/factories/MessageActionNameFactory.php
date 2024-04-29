<?php

namespace Modules\Messages\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MessageActionNameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Messages\Entities\MessageActionName::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Inspections', 'Maintenance', 'Listing', 'Tasks', 'Tenancy', 'contact']),
        ];
    }
}
