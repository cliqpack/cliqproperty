<?php

namespace Modules\UserACL\Entities;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounts\Entities\Account;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\UserACL\Database\factories\AddonFactory::new();
    }
    public function account() {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }
    public function menu()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_id');
    }
}
