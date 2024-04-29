<?php

namespace Modules\UserACL\Entities;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreRequisiteMenuDetail extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\UserACL\Database\factories\PreRequisiteMenuDetailFactory::new();
    }

    public function preMenu()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_id');
    }
    public function addon()
    {
        return $this->hasOne(Addon::class, 'id', 'addon_id')->where('status', 1);
    }
}
