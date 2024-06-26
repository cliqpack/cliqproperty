<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'first_name' => $this->faker->firstNameMale,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'user_type' => $this->faker->randomElement(['Admin', 'Property Manager', 'Owner', 'Tenant', 'Supplier']),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'work_phone' => $this->faker->e164PhoneNumber,
            'mobile_phone' => $this->faker->e164PhoneNumber,
            'company_id' => Company::inRandomOrder()->first()->id,
        ];
    }
}
