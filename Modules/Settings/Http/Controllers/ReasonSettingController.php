<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Settings\Entities\ReasonSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReasonSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {

            $reason = ReasonSetting::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $reason,
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
            // return $request->all();
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:Gain,Lost',
                'reason' => 'nullable|string',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            $companyId = Auth::guard('api')->user()->company_id;
            $type = $request->type;

            ReasonSetting::create([
                'type' => $type,
                'reason' => $request->reason,
                'company_id' => $companyId,
                // 'system_supplied' => $request->system_supplied, // You can add this line if needed
            ]);

            // Log::info('Reason setting created successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Reason setting created successfully',

            ], 200);
        } catch (\Throwable $th) {
            // Log::error('Error in createReasonSetting method: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
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
                // Seller Contact
                'reason'             => $request->reason,
                'type'            => $request->type,
                'company_id'            => auth('api')->user()->company_id,


            );
            $validator = Validator::make($attributeNames, []);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $reasonSetting = ReasonSetting::where('id', $id)->where('company_id', auth('api')->user()->company_id);

                $reasonSetting->update([
                    "reason"    => $request->reason ? $request->reason : null,
                    "type"   => $request->type ? $request->type : null,
                    "system_supplied"    => $request->system_supplied ? $request->system_supplied : null,
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
            $reasonSetting = ReasonSetting::whereIn('id', $request['id'])->delete();
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
