<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Account;

class AccountSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {

            $account = Account::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'account' => $account,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('settings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $accountSetting = new Account();

            $accountSetting->account_name   = $request->account_name;
            $accountSetting->account_type   = $request->account_type;
            $accountSetting->type           = $request->type;
            $accountSetting->description    = $request->description;
            $accountSetting->account_number = $request->account_number;
            $accountSetting->tax            = $request->tax;
            $accountSetting->hidden         = $request->hidden;
            $accountSetting->company_id     = auth('api')->user()->company_id;
            $accountSetting->save();
            return response()->json([
                'message' => 'account setting added successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $account = Account::where('company_id', auth('api')->user()->company_id)->where('id', $id)->first();
            return response()->json([
                'account' => $account,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('settings::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $attributeNames = array(
                // Seller Contact
                'account_name'             => $request->account_name,
                'account_type'            => $request->account_type,
                'type'            => $request->type,
                'description'             => $request->description,
                'account_number'            => $request->account_number,
                'tax'            => $request->tax,
                'hidden'            => $request->hidden,
                'company_id'            => auth('api')->user()->company_id,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $label = Account::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $label->update([
                    "account_name"    => $request->account_name ? $request->account_name : null,
                    "account_type"   => $request->account_type ? $request->account_type : null,
                    "type"   => $request->type ? $request->type : null,
                    "description"    => $request->description ? $request->description : null,
                    "account_number"   => $request->account_number ? $request->account_number : null,
                    "tax"   => $request->tax ? $request->tax : null,
                    "hidden"   => $request->hidden ? $request->hidden : null,
                ]);
            }
            return response()->json([

                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Request $request)
    {
        try {
            $account = Account::whereIn('id', $request['id'])->delete();
            return response()->json([
                'status'  => 'success',
                'message' => 'successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
