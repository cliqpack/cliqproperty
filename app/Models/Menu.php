<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Module\ModuleModel;

class Menu extends Model
{
    use HasFactory;
    protected $fillable = ['menu_title', 'slug','parent_id','sort_order'];


    public function modules(){
        return $this->hasMany(ModuleModel::class,'menu_id','id');
    }
}
