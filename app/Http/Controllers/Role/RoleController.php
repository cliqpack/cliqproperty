<?php

namespace App\Http\Controllers\Role;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Role\RoleModel;
use App\Models\Module\ModuleModel;
use App\Models\Module\ModuleDetailsModel;
use App\Models\User;
use App\Models\UserRolesModel;
use App\Models\Role\RolesDetailsModel;

class RoleController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('role.roleInsert');
    }

    public function roleInsertAjaxRequest(Request $request)
    {

        //getting the current user Name
        $userName = auth('api')->user()->first_name;
        $defaultStatus = 0;
        //gettings attributes
        $attributeNames = array(
            'name'        => $request->roleName,
            'created_by'  => $userName,
            'soft_delete' => $defaultStatus

        );



        //return dd($attributeNames);

        //validating the attributes
        $validator = Validator::make($attributeNames, [
            'name'                    => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
        } else {
            RoleModel::create($attributeNames);

            return response()->json("Success");
        }
    }

    public function getRoleModules($id)
    {
        //  $data=RolesDetailsModel::where('role_id',$id)->get();


        $role_details = RolesDetailsModel::where('role_id', $id)->pluck('module_id')->toArray();

        $module_details = ModuleModel::whereIn('id', $role_details)->pluck('menu_id')->toArray();

        $menu_details = Menu::whereIn('id', $module_details)->get();

        return response()->json(["menu" => $menu_details, "role_id" => $id]);
    }

    public function deleteRoleDetails($id, $role_id)
    {
        //$data=RolesDetailsModel::where('id',$id)->delete();
        // return $id;
        $module_details = ModuleModel::where('menu_id', $id)->pluck('id')->toArray();

        $role_details = RolesDetailsModel::where('role_id', $role_id)->whereIn('module_id', $module_details)->pluck('id')->toArray();

        RolesDetailsModel::whereIn('id', $role_details)->delete();
        return response()->json("success");
    }

    public function rolesDeleteAjax(Request $request)
    {
        $id = $request->role_id;

        $role = RoleModel::findOrFail($id);
        $deletedAttribute = 1;
        $attributeNames = array(
            'soft_delete' => $deletedAttribute
        );
        $role->update($attributeNames);
        return response()->json("Deleted!");
    }


    public function roleAssignUserInsertAjax(Request $request)
    {

        $userName = auth('api')->user()->first_name;
        $defaultStatus = 0;

        $attributeNames = array(
            'role_id'               => $request->role,
            'user_id'               => $request->user,
            'created_by'            => $userName,
            'soft_delete'           => $defaultStatus
        );

        $validator = Validator::make($attributeNames, [
            'role_id'               => 'required',
            'user_id'               => 'required',
            'created_by'            => 'required',
            'soft_delete'           => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 200);
        } else {

            //check if already exists
            if (UserRolesModel::where('role_id', $attributeNames['role_id'])
                ->where('user_id', $attributeNames['user_id'])
                ->exists()
            ) {
                return response()->json(array('warning' => "Record Already Exists"));
            } else {
                //work goes here
                UserRolesModel::create($attributeNames);
                return response()->json("Success");
            }
        }
    }

    public function rolesView()
    {
        //gettings all the roles

        $roles = RoleModel::all('id', 'name', 'created_by', 'soft_delete')->where('soft_delete', 0);
        //dd($roles);
        return view('role.rolesView', compact('roles'));
    }

    //role assign view
    public function rolesAssign()
    {
        //getting all the roles and module
        $roles = RoleModel::all('id', 'name', 'soft_delete')->where('soft_delete', 0)->where('id', '!=', 1);
        $modules = ModuleModel::all('id', 'name', 'soft_delete')->where('soft_delete', 0);

        $data = [
            'roles'   => $roles,
            'modules' => $modules
        ];
        return view('role.roleAssign', $data);
    }

    // getting all the roles
    public function getAllRoles()
    {
        $roles = RoleModel::select('id', 'name', 'created_by', 'soft_delete')->where('soft_delete', 0)->get();
        $data = ["roles" => $roles];
        return response()->json($data, 200);
    }
    //getting the role
    public function getRole($id)
    {
        $role = RoleModel::findorFail($id);
        return response()->json($role, 200);
    }

    //get module by role
    public function getmodulebyrole($id)
    {
        //getting the module_id associated with the role_id from roles_details table
        $filtered_module_ids = RolesDetailsModel::where('role_id', $id)->pluck('module_id')->toArray();

        //getting all the module associated wuth module_id array..thats why whereIn
        $module_names = ModuleModel::whereIn('id', $filtered_module_ids)->pluck('name')->toArray();
        // ->map(function ($item) {
        // return ['module_name' => $item['name']];
        // })

        return response()->json($module_names, 200);
    }

    public function updateRole($id)
    {
        $role = RoleModel::findOrFail($id);

        $attributeNames = array(
            'name'        => request()->roleName
        );

        $validator = Validator::make($attributeNames, [
            'name'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        } else {

            $role->update($attributeNames);

            return response()->json("Success");
        }
    }

    public function roleAssignUser()
    {
        //getting all user and roles
        $users = User::all('id', 'first_name');
        $roles = RoleModel::select('id', 'name', 'soft_delete')->where('soft_delete', 0)->get();

        $data = [
            'users'     => $users,
            'roles'     => $roles

        ];

        //return view('role.roleAssignUser', $data);
        return response()->json($data);
    }

    public function roleModuleAssignAjax(Request $request)
    {

        $userName = auth('api')->user()->first_name;
        $defaultStatus = 0;
        $modules = '';
        if (is_array($request->module) == 1) {
            $modules = $request->module;
        } else {
            $modules = explode(",", $request->module);
        }
        foreach ($modules as $module) {
            $attributeNames = array(
                'role_id'               => $request->role,
                'module_id'             => $module,
                'created_by'            => $userName,
                'soft_delete'           => $defaultStatus
            );

            $validator = Validator::make($attributeNames, [
                'role_id'                   => 'required',
                'module_id'                 => 'required',
                'created_by'                => 'required',
                'soft_delete'               => 'required'

            ]);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                //checking existance
                if (RolesDetailsModel::where('role_id', $attributeNames['role_id'])
                    ->where('module_id', $attributeNames['module_id'])
                    ->exists()
                ) {
                    return response()->json(array('warning' => "Record Already Exists"));
                } else {
                    //work goes here
                    RolesDetailsModel::create($attributeNames);
                }
            }
        }
        return response()->json("Success", 200);
    }


    public function userRoles()
    {

        $all_users = User::with('roles.role','company')->get();

        return response()->json(['data' => $all_users, 'message' => 'Successful']);
    }


    public function userRolesDelete(Request $request)
    {
        UserRolesModel::where('id', $request->id)->first()->delete();
        return response()->json(['message' => 'Successful']);
    }
}
