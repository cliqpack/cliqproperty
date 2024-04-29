<?php

namespace Modules\Contacts\Database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;

class PropertyMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertyMember::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'member_type' => $this->faker->randomElement(['important', 'new', 'old']),
            'property_id' => Properties::inRandomOrder()->first()->id,
            'member_id' => User::inRandomOrder()->first()->id,
        ];
    }
}
