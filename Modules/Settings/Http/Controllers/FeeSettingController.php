<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Settings\Entities\FeeSetting;

class FeeSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $feeSetting = FeeSetting::where('company_id', auth('api')->user()->company_id)->with('account')->get();
            return response()->json([
                'data' => $feeSetting,
                'message' => 'success',
                'status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function ownershipFees()
    {
        try {
            $feeSetting = FeeSetting::where('charging', 'Ownership')->where('company_id', auth('api')->user()->company_id)->with('account')->get();
            return response()->json([
                'data' => $feeSetting,
                'message' => 'success',
                'status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
            ], 500);
        }
    }
    public function folioFees()
    {
        try {
            $feeSetting = FeeSetting::where('charging', 'Folio')->where('company_id', auth('api')->user()->company_id)->with('account')->get();
            return response()->json([
                'data' => $feeSetting,
                'message' => 'success',
                'status' => 'Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error',
                'error' => ['error'],
                'message' => $th->getMessage(),
                'data' => []
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
            $attributeNames = array(
                'display_name' => $request->display_name,
                'charging' => $request->charging,
                'fee_type' => $request->fee_type,
                'value' => $request->value,
                'price' => $request->price,
                'account_id' => $request->account_id,
                'menu_id' => $request->menu_id,
                'status' => $request->status,
                'note' => $request->notes,
                'company_id'    => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                // 'name' => 'required',
                // 'price' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $fee = new FeeSetting();
                $fee->display_name = $request->display_name;
                $fee->charging = $request->charging;
                $fee->fee_type = $request->fee_type;
                $fee->value = $request->value;
                $fee->account_id = $request->account_id;
                $fee->status = $request->status ? $request->status : false;
                $fee->frequnecy_type = $request->frequencyType;
                $fee->time = $request->time;
                $fee->note = $request->notes;
                if ($request->frequencyType === 'Weekly') {
                    $fee->weekly = $request->weekName;
                } elseif ($request->frequencyType === 'Monthly') {
                    $fee->monthly = $request->dayOfMonth;
                } elseif ($request->frequencyType === 'Yearly') {
                    $fee->yearly = $request->dayOfMonth . '/' . $request->month;
                }
                $fee->company_id = auth('api')->user()->company_id;
                $fee->save();
                return response()->json(['message' => 'successful', 'status' => 'Success'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
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
        return view('settings::show');
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
                'display_name' => $request->display_name,
                'charging' => $request->charging,
                'fee_type' => $request->fee_type,
                'value' => $request->value,
                'account_id' => $request->account_id,
                'status' => $request->status,
                'note' => $request->notes,
                'company_id'    => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                // 'name' => 'required',
                // 'price' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                FeeSetting::where('id', $id)->where('company_id', auth('api')->user()->company_id)->update([
                    'display_name' => $request->display_name,
                    'charging' => $request->charging,
                    'fee_type' => $request->fee_type,
                    'value' => $request->value,
                    'account_id' => $request->account_id,
                    'status' => $request->status,
                    'frequnecy_type' => $request->frequencyType,
                    'time' => $request->time,
                    'note' => $request->notes,
                    'weekly' => $request->weekName,
                    'monthly' => $request->dayOfMonth,
                    'yearly' => $request->dayOfMonth . '/' . $request->month,
                ]);
                return response()->json(['message' => 'successful', 'status' => 'Success'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
