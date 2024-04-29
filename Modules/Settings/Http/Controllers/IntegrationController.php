<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Settings\Entities\SettingListingProvider;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        try {

            $companyId = auth('api')->user()->company_id;
            $records = SettingListingProvider::where('company_id', $companyId)->get();

            return response()->json([
                'status' => true,
                'message' => 'Records fetched successfully',
                'data' => $records,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
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
            $companyId = auth('api')->user()->company_id;

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                // 'agent_id' => 'nullable|string',
                'is_available' => 'nullable|boolean',
                'is_enable' => 'nullable|boolean',
                'has_listing_provider_import_in_progress' => 'nullable|boolean',
                'company_id' => 'nullable|exists:companies,id',
                'external_provider_type' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            $data = $request->only([
                'name',
                'agent_id',
                'is_available',
                'is_enable',
                'has_listing_provider_import_in_progress',
                'company_id',
                'external_provider_type',
            ]);

            $conditions = [
                'name' => $data['name'],
                'company_id' => auth('api')->user()->company_id,

            ];

            $record = SettingListingProvider::updateOrCreate($conditions, $data);

            return response()->json([
                'status' => 'Success',
                'message' => 'Record created/updated successfully',
                'data' => $record,
            ], 200);
        } catch (\Throwable $th) {
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
        try {

            $companyId = auth('api')->user()->company_id;

            $record = SettingListingProvider::where('company_id', $companyId)->find($id);

            if (!$record) {
                return response()->json([
                    'status' => false,
                    'message' => 'Record not found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Record fetched successfully',
                'data' => $record,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
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
            $companyId = auth('api')->user()->company_id;
    
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                'is_available' => 'nullable|boolean',
                'is_enable' => 'nullable|boolean',
                'has_listing_provider_import_in_progress' => 'nullable|boolean',
                'company_id' => 'nullable|exists:companies,id',
                'external_provider_type' => 'nullable|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }
    
            $data = $request->only([
                'name',
                'agent_id',
                'is_available',
                'is_enable',
                'has_listing_provider_import_in_progress',
                'company_id',
                'external_provider_type',
            ]);
    
            // Additional condition for matching ID and company_id
            $conditions = [
                'id' => $id,
                'company_id' => auth('api')->user()->company_id,
            ];
    
            $record = SettingListingProvider::where($conditions)->update($data);
    
            if ($record) {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Record updated successfully',
                    'data' => $record,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'error' => 'Record not found or not updated',
                    'message' => 'Record not found or not updated',
                    'data' => [],
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
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
        try {

            $companyId = auth('api')->user()->company_id;

            $record = SettingListingProvider::where('company_id', $companyId)->find($id);

            if (!$record) {
                return response()->json([
                    'status' => false,
                    'message' => 'Record not found',
                    'data' => [],
                ], 404);
            }

            $record->delete();

            return response()->json([
                'status' => true,
                'message' => 'Record deleted successfully',
                'data' => [],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
