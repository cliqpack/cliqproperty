<?php

namespace Modules\UserACL\Entities;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuPrice extends Model
{
    use HasFactory;

    protected $fillable = ['menu_id'];

    protected static function newFactory()
    {
        // return \Modules\UserACL\Database\factories\MenuPriceFactory::new();
    }

    public function menu()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_id');
    }
}
