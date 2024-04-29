<?php

namespace Modules\Contacts\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Entities\Contacts;
use Modules\Inspection\Entities\Inspection;
use Modules\Listings\Entities\listing;
use Modules\Maintenance\Entities\Maintenance;
use Modules\Properties\Entities\Properties;
use Modules\Tasks\Entities\Task;

class ContactActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Contacts\Entities\ContactActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'property_id' => Properties::inRandomOrder()->first()->id,
            'contact_id' => Contacts::inRandomOrder()->first()->id,
            'task_id' => Task::inRandomOrder()->first()->id,
            'inspection_id' => Inspection::inRandomOrder()->first()->id,
            'maintenance_id' => Maintenance::inRandomOrder()->first()->id,
            'listing_id' => listing::inRandomOrder()->first()->id,
        ];
    }
}
