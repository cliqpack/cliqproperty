<?php

namespace Modules\Maintenance\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\TenantContact;
use Modules\Properties\Entities\Properties;

class MaintenanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Maintenance\Entities\Maintenance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'reported_by' => $this->faker->text(10),
            // 'status' => $this->faker->word(5),
            'access' => $this->faker->word(5),
            'due_by' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
            'manager_id' => User::inRandomOrder()->first()->id,
            'tenant_id' => TenantContact::inRandomOrder()->first()->id,
            'summary' => $this->faker->title(10),
            'description' => $this->faker->text(20),
            'work_order_notes' => $this->faker->text(20),
            // 'status' => $this->faker->word(5),
            'company_id' => Company::inRandomOrder()->first()->id,

        ];
    }
}
