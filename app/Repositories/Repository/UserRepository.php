<?php

namespace App\Repositories\Repository;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contructs\UserContruct;
use App\Models\User;
use App\Models\UserRolesModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\TenantContact;

//use DataTables;

class UserRepository implements UserContruct
{

    public function store(array $user)
    {
        try {
            if ($user['user_type'] == "Property Manager") {
                $validator = Validator::make($user, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'email' => 'required|email|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'company_id' => 'required',
                    'password' => 'required|min:8'
                ]);
            } else {
                $validator = Validator::make($user, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'email' => 'required|email|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'password' => 'required|min:8'
                ]);
            }
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'data' => null,
                    'error' => $validator->errors()
                ], 422);
            } else {

                $user = User::create([
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'user_type' => $user['user_type'],
                    'company_id' => $user['company_id'],
                    'work_phone' => $user['work_phone'],
                    'mobile_phone' => $user['mobile_phone'],
                    'password' =>  Hash::make($user['password'])
                ]);


                event(new Registered($user));

                return response()->json([
                    'data' => null,
                    'message' => 'Please Verify Email'
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }
    public function store_in_manager(array $user)
    {
        try {
            if ($user['user_type'] == "Property Manager") {
                $validator = Validator::make($user, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'email' => 'required|email|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'company_id' => 'required',
                    'password' => 'required|min:8',
                    'role_id' => 'required',
                ]);
            } else {
                $validator = Validator::make($user, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'email' => 'required|email|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'password' => 'required|min:8'
                ]);
            }
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'data' => null,
                    'error' => $validator->errors()
                ], 422);
            } else {

                $user_in = User::create([
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'user_type' => $user['user_type'],
                    'company_id' => $user['company_id'],
                    'work_phone' => $user['work_phone'],
                    'mobile_phone' => $user['mobile_phone'],
                    'password' =>  Hash::make($user['password'])
                ]);


                //check if already exists
                if (UserRolesModel::where('role_id', $user['role_id'])
                    ->where('user_id', $user_in->id)
                    ->exists()
                ) {
                    return response()->json(array('warning' => "Record Already Exists"));
                } else {
                    //work goes here
                    UserRolesModel::create([
                        'role_id'               => $user['role_id'],
                        'user_id'               => $user_in->id,
                        'created_by'            => auth('api')->user()->first_name,
                        'soft_delete'           => 0,
                    ]);
                }
                event(new Registered($user_in));

                return response()->json([
                    'data' => null,
                    'message' => 'Please Verify Email'
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }
    public function reg_other(array $user)
    {
        try {
            if ($user['user_type'] != "Property Manager") {
                $validator = Validator::make($user, [
                    'first_name' => 'required|min:2|max:255',
                    'last_name' => 'required|min:2|max:255',
                    'user_type' => 'required|min:2|max:255',
                    'password' => 'required|min:8',
                    'user_id' => 'required'
                ]);
            }
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'data' => null,
                    'error' => $validator->errors()
                ], 422);
            } else {

                $user = User::where('id', $user['user_id'])->update([
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'user_type' => $user['user_type'],
                    'work_phone' => $user['work_phone'],
                    'mobile_phone' => $user['mobile_phone'],
                    'password' =>  Hash::make($user['password'])
                ]);


                return response()->json([
                    'data' => null,
                    'message' => 'Registration Successful'
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }

    public function store_other(array $user)
    {
        try {

            $validator = Validator::make($user, [
                'email' => 'required|email|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'data' => null,
                    'error' => $validator->errors()
                ], 422);
            } else {

                $user = User::create([
                    'first_name' => "guest",
                    'last_name' => "last",
                    'email' => $user['email'],
                    'user_type' => "Guest",
                    'company_id' => "0",
                    'work_phone' => "1111111",
                    'mobile_phone' => '11',
                    'password' =>  Hash::make('guest')
                ]);

                event(new Registered($user));


                return response()->json([
                    'data' => null,
                    'message' => 'Please Verify Email'
                ], 200);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }

    public function findUserInfo()
    {
        try {

            $user = auth()->user();
            // return $user;
            return response()->json([
                'message' => 'Login successful',
                'data' => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        }
    }

    public function findUser(array $user)
    {
        try {
            $validator = Validator::make($user, [
                'email' => 'required|email',
                'password' => 'required|min:8'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'token' => null,
                    'message' => 'Login Error',
                    'error' => $validator->errors()
                ], 422);
            } else {
                $data = [
                    'email' => $user['email'],
                    'password' => $user['password']
                ];
                try {

                    $user = User::where('email', $user['email'])->first();


                    if ($user->email_verified_at != null) {

                        if (auth()->attempt($data) != "") {
                            $userToken = auth()->user()->createToken('Token')->accessToken;

                            // Owner Access check & tenant access check
                            $owner = OwnerContact::whereEmail($user['email'])->first();
                            $tenant = TenantContact::whereEmail($user['email'])->first();
                            $ownerAccess = empty($owner) ? false : true;
                            $tenantAccess = empty($tenant) ? false : true;
                            if (!empty($owner)) {
                                User::where('email', $user['email'])->update([
                                    'company_id' => $owner->company_id
                                ]);
                            }
                            if (!empty($tenant)) {
                                User::where('email', $user['email'])->update([
                                    'company_id' => $tenant->company_id
                                ]);
                            }

                            // -----------------------------

                            return response()->json([
                                'token' => $userToken,
                                'ownerAccess' => $ownerAccess,
                                'tenantAccess' => $tenantAccess,
                                'user' => auth()->user(),
                                'message' => 'Login successful'
                            ], 200);
                        } else {
                            return response()->json([
                                'token' => null,
                                'message' => 'Unauthorized',
                                'error' => 'Unauthorized'
                            ], 410);
                        }
                    } else {
                        return response()->json([
                            'token' => null,
                            'message' => 'Email is not verified',
                            'error' => 'Email is not verified'
                        ], 410);
                    }
                } catch (Exception $e) {
                    return response()->json([
                        'message' => 'Email is not Registered',
                        'data' => null,
                        'error' => $e->getMessage()
                    ], 410);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Opps! A Query Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 411);
        }
    }

    public function findVerified($id)
    {
        try {
            
            $front_url = getenv("FRONT_API");
            $user = User::where('id', $id);

            if ($user->first()->email_verified_at == null) {
                if ($user->first()->user_type == "Guest") {
                    $user->update([
                        "email_verified_at" => NOW()
                    ]);
                    return redirect($front_url. '/' .'register-owner-tenant/' . $id);
                } else {
                    $user->update([
                        "email_verified_at" => NOW()
                    ]);
                    return redirect($front_url);
                }
            } else {
                return "You are Already Verified Please Login";
            }
        } catch (Exception $e) {
            return "We dont Have this user";
        }
    }

    public function forgotPassword($user)
    {
        try {

            $status = Password::sendResetLink(
                $user
            );
            return $status === Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Opps! An Exception',
                'data' => null,
                'error' => $e->getMessage()
            ], 410);
        }
    }
    public function resetPassword($id)
    {
        try {
            $url = 'http://localhost:3000/forgot-password/' . $id;
            return $url;
        } catch (Exception $e) {
            return "We dont Have this user";
        }
    }
}
