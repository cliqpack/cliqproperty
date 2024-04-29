<?php

namespace Modules\Settings\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Modules\Settings\Entities\MessagePortfolioEmailSetting;
use Modules\Settings\Entities\MessageSetting;

class MessageSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {

            $companyId = Auth::guard('api')->user()->company_id;

            $messageSettings = MessageSetting::where('company_id', $companyId)->with('messageSetting')->first();

            if (!$messageSettings) {
                return response()->json([
                    'status' => false,
                    'message' => 'message setting not found',
                    'data' => [],
                ], 404);
            }



            $responseData = [
                'status' => true,
                'message' => 'message setting retrieved successfully',
                'data' => $messageSettings,
            ];

            return response()->json($responseData, 200);
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
            $companyId = Auth::guard('api')->user()->company_id;
            // $userFirstName = Auth::guard('api')->user()->first_name;
            // $userLastName = Auth::guard('api')->user()->last_name;
            // $companyName = Company::where('id', $companyId)->pluck('company_name');
            // return $companyName;
            // return $userFirstName;

            $validationRules = [
                'email_from_name_type' => 'nullable|string',
                'sending_behaviour' => 'nullable|string',
                'email_will_be_sent_as' => 'nullable|string',
                'sms_from' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            $data = $request->only([
                'email_from_name_type',
                'sending_behaviour',
                'email_will_be_sent_as',
                'sms_from',
            ]);

            $data['company_id'] = $companyId;

            // Find the existing record or create a new one
            $messageSettings = MessageSetting::updateOrCreate(
                ['company_id' => $companyId],
                $data
            );

            return response()->json([
                'status' => true,
                'message' => 'Message settings created/updated successfully',
                'data' => $messageSettings,
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


    // public function emailSentAs(Request $request)
    public function emailSentAs()
    {
        $companyId = Auth::guard('api')->user()->company_id;
        $userFirstName = Auth::guard('api')->user()->first_name;
        $userLastName = Auth::guard('api')->user()->last_name;
        $fullName = $userFirstName . ' ' . $userLastName;
        $companyName = Company::where('id', $companyId)->value('company_name');
        // return $companyName;


        // $emailFromName = null;

        // if ($request->email_from_name_type === 'userAndCompanyName') {
        //     $emailFromName = $fullName . '-' . $companyName;
        // } elseif ($request->email_from_name_type === 'userName') {
        //     $emailFromName = $fullName;
        // } elseif ($request->email_from_name_type === 'companyName') {
        //     $emailFromName = $companyName;
        // }

        return response()->json([
            'userName' => $fullName,
            'companyName' => $companyName,
            'status' => 'email from name  successfull'
        ], 200);
    }
    public function createOrUpdateMessagePortfolioEmailSettings(Request $request)
    {
        try {
            $companyId = Auth::guard('api')->user()->company_id;

            $validationRules = [
                'portfolio_email' => 'nullable|string',
                'message_setting_id' => 'nullable|integer',
            ];

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            $data = $request->only([
                'portfolio_email',
                'message_setting_id',
            ]);

            $data['company_id'] = $companyId;

            $check=MessagePortfolioEmailSetting::where('portfolio_email',$request->portfolio_email)->whereNotIn('company_id',[$companyId])->get();
            if ($check->count() > 0) {
                return response()->json(['status' => false,
                'error' => 'please enter unique address',
                'data' => [],
            ], 500);
            }

            // Find the existing record or create a new one
            $messagePortfolioEmailSettings = MessagePortfolioEmailSetting::updateOrCreate(
                ['company_id' => $companyId],
                $data
            );
            $messageSetting = MessageSetting::updateOrCreate(
                ['company_id' => $companyId],
                ['email_will_be_sent_as' => $data['portfolio_email']]
            );

            return response()->json([
                'status' => true,
                'message' => 'Message portfolio email settings created/updated successfully',
                'data' => $messagePortfolioEmailSettings,
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
