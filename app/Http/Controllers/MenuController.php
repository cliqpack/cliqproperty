<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Module\ModuleModel;
use App\Models\Role\RolesDetailsModel;
use App\Models\User;
use App\Models\UserRolesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    public function index()
    {
        $user = auth('api')->user()->id;
        $user_roles = UserRolesModel::where('user_id', $user)->pluck('role_id')->toArray();

        $role_details = RolesDetailsModel::whereIn('role_id', $user_roles)->pluck('module_id')->toArray();

        $module_details = ModuleModel::whereIn('id', $role_details)->pluck('menu_id')->toArray();

        $menu_details = Menu::whereIn('id', $module_details)->get();

        return response()->json(['data' => $menu_details, 'message' => 'Successful']);
    }
}
