<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    //

    public function users()
    {
        $users = User::where('company_id', auth('api')->user()->company_id)->where('user_type', 'Property Manager')->with('roles.role')->get();

        $value = ['id' => '', 'full_name' => '(None)', 'first_name' => '(None)', 'last_name' => ''];
        $users->prepend($value);

        return response()->json($users);
    }
    public function userOwners()
    {
        $users = User::where('user_type', 'Owner')->where('company_id', auth('api')->user()->company_id)->with('roles.role')->get();

        return response()->json($users);
    }

    public function manager()
    {
        $users = User::where('user_type', 'Property Manager')->with('company')->get();

        return response()->json($users);
    }

    public function companyManager()
    {
        try {
            $users = User::where('user_type', 'Property Manager')->where('company_id', auth('api')->user()->company_id)->with('company')->get();

            return response()->json($users);
        } catch (\Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage(), 'error' => [], 'data' => []]);
        }
    }

    public function userinfodata($id)
    {

        $user = User::findOrFail($id);

        // $data = [
        //     'modules' => $users
        // ];
        return response()->json($user);
    }


    public function changepassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|min:8',
                'oldPassword' => 'required|min:8'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            }
            $userdata = auth('api')->user();

            $user = User::find($userdata->id);
            $oldexistingbyCrypt           = $user->password;
            $user->password               = bcrypt($request->password);

            if (\Hash::check($request->oldPassword, $oldexistingbyCrypt)) {
                $user->save();
                return response()->json(['status' => true, 'message' => 'Password has been changed successfully', 'error' => [], 'data' => []]);
            } else {


                return response()->json(['status' => false, 'message' => 'Wrong old password given', 'error' => [], 'data' => []], 500);
            }
        } catch (\Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage(), 'error' => [], 'data' => []], 500);
        }
    }


    public  function updateInfo(Request $request)
    {
        try {
            $user_id = $request->id;

            $user = User::where('id', $user_id);
            if ($request->user_type == 'Property Manager') {
                $user->update([
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    // 'email' => $request->email,
                    'user_type' => $request->user_type,
                    // 'company_id' => $request->company_id,
                    'work_phone' => $request->work_phone,
                    'mobile_phone' => $request->mobile_phone,
                    'address' => $request->address,
                    'facebook_link' => $request->facebook_link,
                    'linked_in_link' => $request->linked_in_link,
                    'twitter_link' => $request->twitter_link,
                ]);
            } else if ($request->user_type !== 'Property Manager') {
                $user->update([
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    // 'email' => $request->email,
                    'user_type' => $request->user_type,
                    'company_id' => null,
                    'work_phone' => $request->work_phone,
                    'mobile_phone' => $request->mobile_phone,
                    'address' => $request->address,
                    'facebook_link' => $request->facebook_link,
                    'linked_in_link' => $request->linked_in_link,
                    'twitter_link' => $request->twitter_link,
                ]);
            } else {
                $user->update([
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    // 'email' => $request->email,
                    // 'user_type' => $request->user_type,
                    'work_phone' => $request->work_phone,
                    'mobile_phone' => $request->mobile_phone,
                    'address' => $request->address,
                ]);
            }
            return response()->json([
                'message' => 'Profile information has been updated successfully',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function destroy(Request $request)
    {
        $id = $request->user;

        $user = User::findOrFail($id)->delete();

        return response()->json(["success"]);
    }
    public function updateProfilePic(Request $request)
    {
        try {
            $imageUpload = User::findOrFail(auth('api')->user()->id);
            if ($request->file('image')) {
                $file = $request->file('image');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                // $file->move(public_path('public/Image'), $filename);
                // $path = config('app.asset_s') . '/Image';
                $path = config('app.asset_s') . '/Image';
                $filename_s3 = Storage::disk('s3')->put($path, $file);
                // $imageUpload->property_image = $filename_s3;
                $imageUpload->profile = $filename_s3;
            }
            $imageUpload->save();
            // $imagePath = "http://localhost:8000/public/Image" . $filename;
            return response()->json(['message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage(), 'error' => [], 'data' => []], 500);
        }
    }
}
