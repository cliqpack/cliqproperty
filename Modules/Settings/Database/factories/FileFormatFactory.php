<?php

namespace Modules\Settings\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FileFormatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Settings\Entities\FileFormat::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'file_name' => $this->faker->unique()->randomElement(['Standard ABA format', 'ABA format with 1 debit per file', 'ABA format with 1 debit per row']),
        ];
    }
}
