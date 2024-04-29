<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Menu;
use App\Models\Role\RoleModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Auth\Events\Registered;

class AdminPanelController extends Controller
{
    //property login
    public function index()
    {
        return view('auth.login');
    }

    public function home()
    {
        return view('home');
    }

    public function company()
    {

        return view('company');
    }

    public function companyAdmin()
    {
        $companies = Company::all();

        $data = [
            'companies' => $companies
        ];
        return view('companyAdmin', $data);
    }
    public function companiesAdd(Request $request)
    {
        //    return $request->all();
        try {

            $attributeNames = array(
                'company_name'        => $request->company_name,
                'phone'              => $request->mobile_phone,
                'address'            => $request->address,
            );

            $validator = Validator::make($attributeNames, [
                'company_name'        => 'required',
                'phone'              => 'required',
                'address'              => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $company = Company::create([
                    'company_name' => $request->company_name,
                    'address' => $request->address,
                    'phone' => $request->mobile_phone,
                    'slug' => Str::of($request->company_name)->slug('-'),

                ]);

                $attributeNames1 = array(
                    'first_name'        => $request->first_name,
                    'last_name'              => $request->last_name,
                    'email'            => $request->email,
                    'user_type'              => $request->user_type,
                    'password'            => $request->password,
                );

                $validator1 = Validator::make($attributeNames1, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'email' => 'required|email|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'password' => 'required|min:8'
                ]);
                if ($validator1->fails()) {
                    return response()->json([
                        'message' => 'Validation Error',
                        'data' => null,
                        'error' => $validator1->errors()
                    ], 422);
                } else {

                    $user = User::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                        'user_type' => $request->user_type,
                        'company_id' => $company->id,
                        'work_phone' => $request->work_phone,
                        'mobile_phone' => $request->mobile_phone,
                        'password' =>  Hash::make($request->password)
                    ]);

                    event(new Registered($user));
                    return response()->json([
                        'data' => null,
                        'message' => 'Please Verify Email'
                    ], 200);
                }
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }

    public function menu()
    {
        return view('menu');
    }
    public function module()
    {
        $menus = Menu::all();

        $data = [
            'menus' => $menus
        ];
        return view('module',$data);
    }
    public function role()
    {
        $menus = Menu::all();

        $data = [
            'menus' => $menus
        ];
        return view('role',$data);
    }

    public function userRole()
    {
        $menus = User::all();
        $roles=RoleModel::where('soft_delete',0)->get();

        $data = [
            'users' => $menus,
            'roles' => $roles
        ];
        return view('userRoles',$data);
    }
    public function allUser()
    {
        $menus = User::with('company')->get();

        $data = [
            'users' => $menus
        ];
        return view('allUser',$data);
    }
}
