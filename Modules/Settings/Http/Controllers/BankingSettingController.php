<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Settings\Entities\Bank;
use Modules\Settings\Entities\BankingSetting;
use Modules\Settings\Entities\FileFormat;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Entities\DepositeClearance;
use Illuminate\Support\Facades\Auth;

class BankingSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */


    public function index()
    {
        try {
            $bankingSetting = BankingSetting::where('company_id', auth('api')->user()->company_id)->with('company', 'bank', 'fileFormat')->first();
            return response()->json([
                'bankingSetting' => $bankingSetting,
                'message' => 'successfull'
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

    public function settingBankName()
    {
        try {
            $banks = Bank::get();
            return response()->json([
                'bank' => $banks,
                'message' => 'success'
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
    public function settingFileFormat()
    {
        try {
            $fileFormat = FileFormat::get();
            return response()->json([
                'fileFormat' => $fileFormat,
                'message' => 'success'
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
            // $id = $request->id;
            $data = array(
                'account_name' => $request->account_name,
                'bsb' => $request->bsb,
                'account_number' => $request->account_number,
                'unique_identifying_number' => $request->unique_identifying_number,
                'bank_id' => $request->bank_id,
                'eft_payments_enable' => $request->eft_payments_enable,
                'statement_description_as_property_reference' => $request->statement_description_as_property_reference,
                'default_statement_description' => $request->default_statement_description,
                'de_user_id' => $request->de_user_id,
                'file_format_id' => $request->file_format_id,
                'tenant_direct_debitenable_enable' => $request->tenant_direct_debitenable_enable,
                // 'change_to_days_to_clear' => $request->change_to_days_to_clear,
                'bpay_enable' => $request->bpay_enable,
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'bpay_for' => $request->bpay_for,
                'company_id'    => auth('api')->user()->company_id,

            );
            $bankingSetting = BankingSetting::updateOrCreate(
                ['company_id' => auth('api')->user()->company_id],
                $data
            );
            return response()->json([
                'data' => 'Banking setting created successfully'
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
        //
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


    public function createOrUpdateDepositeClearance(Request $request)
    {
        try {
            $customMessages = [
                'clearance_after_days.max' => 'Clearance after days cannot be more than 7.',
            ];
            $validator = Validator::make($request->all(), [
                'data' => 'required|array',
                'data.*.deposit_type' => 'required|string',
                'data.*.clearance_after_days' => 'required|integer|max:7',
                'data.*.notes' => 'nullable|string',
            ], $customMessages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            $companyId = Auth::guard('api')->user()->company_id;

            foreach ($request->data as $depositTypeConfig) {
                $depositType = $depositTypeConfig['deposit_type'];


                $existingRecord = DepositeClearance::where('deposit_type', $depositType)
                    ->where('company_id', $companyId)
                    ->first();

                if ($existingRecord) {

                    $existingRecord->update([
                        'clearance_after_days' => $depositTypeConfig['clearance_after_days'],
                        'notes' => $depositTypeConfig['notes'],
                    ]);
                } else {

                    DepositeClearance::create([
                        'deposit_type' => $depositType,
                        'clearance_after_days' => $depositTypeConfig['clearance_after_days'],
                        'notes' => $depositTypeConfig['notes'],
                        'company_id' => $companyId,
                    ]);
                }
            }


            Log::info('Deposite clearance records created/updated successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Deposite clearance records created/updated successfully',
                'data' => [],
            ], 200);
        } catch (\Throwable $th) {

            Log::error('Error in createOrUpdateDepositeClearance method: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function getDepositeClearance()
    {
        try {

            $companyId = Auth::guard('api')->user()->company_id;
            $depositClearances = DepositeClearance::where('company_id', $companyId)->get();

            return response()->json([
                'status' => true,
                'message' => 'Deposite clearance records retrieved successfully',
                'data' => $depositClearances,
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
