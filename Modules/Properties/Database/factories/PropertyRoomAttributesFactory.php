<?php

namespace Modules\Properties\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Properties\Entities\Properties;
use Modules\Properties\Entities\PropertyRoom;

class PropertyRoomAttributesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Properties\Entities\PropertyRoomAttributes::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'room_id' => PropertyRoom::inRandomOrder()->first()->id,
            'field' => $this->faker->randomElement(['walls/picture hooks', 'built-in wardrobe/shelves', 'Other', 'walls/tiles', 'floor tiles/floor coverings', 'doors/doorway frames', 'windows/screens/window safety devices', 'ceiling/light fittings', 'blinds/curtains', 'lights/power points', 'bath/taps', 'mirror/cabinet/vanity']),

        ];
    }
}
