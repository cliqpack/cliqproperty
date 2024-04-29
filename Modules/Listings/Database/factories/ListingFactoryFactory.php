<?php

namespace Modules\Listings\Database\factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;

class ListingFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Listings\Entities\Listing::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $first_name = $this->faker->text(30);
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'type' => $this->faker->text(10),
            'status' => $this->faker->text(10),

            'company_id' => Company::inRandomOrder()->first()->id,



        ];
    }
}
