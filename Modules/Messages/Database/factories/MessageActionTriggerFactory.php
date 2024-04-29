<?php

namespace Modules\Messages\Database\factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Messages\Entities\MessageActionName;

class MessageActionTriggerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Messages\Entities\MessageActionTriggerTo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'action_id' => function () {
                // Generate a random action_id or select an existing one from the MessageActionName table
                return MessageActionName::inRandomOrder()->first()->id;
            },
            'trigger_to' => $this->faker->randomElement(['Owner', 'Tenant', 'Supplier']),
            'company_id' => function () {
                // Generate a random company_id or select an existing one from the Companies table
                return Company::inRandomOrder()->first()->id;
            },
        ];
    }
}
