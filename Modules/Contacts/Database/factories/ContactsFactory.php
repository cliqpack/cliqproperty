<?php

namespace Modules\Contacts\Database\factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\Contacts::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'reference' => $this->faker->address,

            'first_name' => $this->faker->firstNameMale,
            'last_name' => $this->faker->lastName,
            'salutation' => $this->faker->titleMale,
            'company_name' => $this->faker->company,
            'mobile_phone' => $this->faker->e164PhoneNumber,
            'work_phone' => $this->faker->e164PhoneNumber,
            'home_phone' => $this->faker->e164PhoneNumber,
            'email' => $this->faker->companyEmail,
            'abn' => $this->faker->numberBetween($min = 100000, $max = 300000),
            'notes' => $this->faker->text(10),
            'owner' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'tenant' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'supplier' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'seller' => $this->faker->boolean($chanceOfGettingTrue = 50),
            'company_id' => Company::inRandomOrder()->first()->id,
        ];
    }
}
