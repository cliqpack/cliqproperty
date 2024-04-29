<?php

namespace Modules\Contacts\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Properties\Entities\Properties;

class OwnerContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\OwnerContact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contacts::inRandomOrder()->first()->id,
            'property_id' => Properties::inRandomOrder()->first()->id,
            'reference' => $this->faker->address,
            'first_name' => $this->faker->firstNameMale,
            'last_name' => $this->faker->lastName,
            'salutation' => $this->faker->titleMale,
            'company_name' => $this->faker->company,
            'mobile_phone' => $this->faker->e164PhoneNumber,
            'work_phone' => $this->faker->e164PhoneNumber,
            'home_phone' => $this->faker->e164PhoneNumber,
            'email' => $this->faker->companyEmail,
            'notes' => $this->faker->text(10),
            'company_id' => Company::inRandomOrder()->first()->id,
            'user_id' => User::inRandomOrder()->first()->id,
        ];
    }
}
