<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Settings\Entities\CompanySetting;
use Modules\Settings\Entities\Country;
use Modules\Settings\Entities\Region;
use Modules\Settings\Entities\WorkingHour;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Entities\InspectionReportDisclimer;
use Modules\Settings\Entities\InvoicePaymentInstructionSetting;

class CompanySettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $companySetting = CompanySetting::where('company_id', auth('api')->user()->company_id)->with('company', 'country', 'region')->first();
            return response()->json([
                'companySetting' => $companySetting,
                'message' => 'successfull'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
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
        // return $request;
        try {
            $data = array(
                'portfolio_supplier'           => "Moment Pty Ltd",
                'portfolio_name'               => $request->portfolio_name,
                'country_id'                      => $request->country_id,
                'region_id'                       => $request->region_id,
                'licence_number'               => $request->licence_number,
                'include_property_key_number'  => $request->include_property_key_number,
                'update_inspection_date'       => $request->update_inspection_date,
                'client_access'                => $request->client_access,
                'client_access_url'            => $request->client_access_url,
                'portfolio_id'                 => "MOMPTO0P",
                'working_hours'                => $request->working_hours,
                'invoice_payment_instructions' => $request->invoice_payment_instructions,
                'inspection_report_disclaimer' => $request->inspection_report_disclaimer,
                'rental_position_on_receipts'  => $request->rental_position_on_receipts,
                'show_effective_paid_to_dates' => $request->show_effective_paid_to_dates,
                'include_paid_bills'           => $request->include_paid_bills,
                'bill_approval'                => $request->bill_approval,
                'join_the_test_program'        => $request->join_the_test_program,
                'company_id'                   => auth('api')->user()->company_id,

            );

            $companySetting = CompanySetting::updateOrCreate(

                [
                    'company_id' => auth('api')->user()->company_id
                ],
                $data
            );
            return response()->json([
                'message' => 'Company Setting added successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }


        // try {
        //     $attributeNames = array(
        //         // Buyer Contact
        //         'portfolio_supplier'             => $request->portfolio_supplier,
        //         'portfolio_name'            => $request->portfolio_name,
        //         'country'             => $request->country,
        //         'region'            => $request->region,
        //         'licence_number'          => $request->licence_number,
        //         'include_property_key_number'          => $request->include_property_key_number,
        //         'update_inspection_date'            => $request->update_inspection_date,
        //         'client_access'            => $request->client_access,
        //         'client_access_url'                 => $request->client_access_url,
        //         'portfolio_id'                   => $request->portfolio_id,
        //         'working_hours'                 => $request->working_hours,
        //         'invoice_payment_instructions'                 => $request->invoice_payment_instructions,
        //         'inspection_report_disclaimer'                 => $request->inspection_report_disclaimer,
        //         'rental_position_on_receipts'       => $request->rental_position_on_receipts,
        //         'show_effective_paid_to_dates'         => $request->show_effective_paid_to_dates,
        //         'include_paid_bills'          => $request->include_paid_bills,
        //         'bill_approval'        => $request->bill_approval,
        //         'join_the_test_program'     => $request->join_the_test_program,
        //         'company_id'            => auth('api')->user()->company_id,

        //     );
        //     $validator = Validator::make($attributeNames, [
        //         'portfolio_supplier' => 'required'
        //     ]);
        //     if ($validator->fails()) {

        //         return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
        //     } else {

        //         $companySetting = new CompanySetting();

        //         $companySetting->portfolio_supplier  = $request->portfolio_supplier;
        //         $companySetting->portfolio_name    = $request->portfolio_name;
        //         $companySetting->country   = $request->country;
        //         $companySetting->region    = $request->region;
        //         $companySetting->licence_number   = $request->licence_number;
        //         $companySetting->include_property_key_number = $request->include_property_key_number;
        //         $companySetting->update_inspection_date = $request->update_inspection_date;
        //         $companySetting->client_access   = $request->client_access;
        //         $companySetting->client_access_url   = $request->client_access_url;
        //         $companySetting->portfolio_id        = $request->portfolio_id;
        //         $companySetting->working_hours        = $request->working_hours;
        //         $companySetting->invoice_payment_instructions          = $request->invoice_payment_instructions;
        //         $companySetting->inspection_report_disclaimer          = $request->inspection_report_disclaimer;
        //         $companySetting->rental_position_on_receipts          = $request->rental_position_on_receipts;
        //         $companySetting->show_effective_paid_to_dates          = $request->show_effective_paid_to_dates;
        //         $companySetting->include_paid_bills          = $request->include_paid_bills;
        //         $companySetting->bill_approval          = $request->bill_approval;
        //         $companySetting->join_the_test_program          = $request->join_the_test_program;
        //         $companySetting->company_id   = auth('api')->user()->company_id;

        //         $companySetting->save();

        //         return response()->json([
        //             'message' => 'Company Setting created successfully',
        //             'status' => 'Success',
        //         ], 200);
        //     }
        // } catch (\Exception $ex) {
        //     return response()->json([
        //         "status" => false,
        //         "error" => ['error'],
        //         "message" => $ex->getMessage(),
        //         "data" => []
        //     ], 500);
        // }
    }


    public function countries()
    {
        try {
            $countries = Country::get();
            return response()->json([
                'country' => $countries,
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
    public function region()
    {
        try {
            $region = Region::get();
            return response()->json([
                'region' => $region,
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

    public function working_hour_index()
    {
        try {
            $working_hour = WorkingHour::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $working_hour,
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

    public function working_hour(Request $request)
    {
        try {
            // Validate the request data

            $validator = Validator::make($request->all(), [

                'data' => 'required|array',
                'data.*.day' => 'nullable|string',
                'data.*.work' => 'nullable|boolean',
                'data.*.start_time' => 'nullable|string',
                'data.*.end_time' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation failed',
                    'data' => [],
                ], 400);
            }

            // Retrieve the authenticated user's company_id
            $companyId = Auth::guard('api')->user()->company_id;

            foreach ($request->data as $key => $working) {
                // return $working;
                // Use 'day' and 'company_id' as unique identifiers
                $uniqueIdentifier = ['day' => $working["day"], 'company_id' => $companyId];

                // Data to be updated or inserted
                $data = [
                    'work' => $working["work"] ?? null,
                    'start_time' => $working["start_time"] ?? null,
                    'end_time' => $working["end_time"] ?? null,
                    'company_id' => $companyId,

                ];

                // Update or insert the record
                WorkingHour::updateOrInsert($uniqueIdentifier, $data);
            }

            // Log the successful operation
            // Log::info('Working hours updated/inserted successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Working hours updated/inserted successfully',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            // Log the error
            // Log::error('Error in working_hour method: ' . $th->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'An error occurred',
                'message' => $th->getMessage(),
                'data' => [],
            ], 500);
        }
    }
    public function invoicePaymentInstructions(Request $request)
    {
        try {
            // Validate the request data if needed

            // $companyId = $request->input('company_id');

            $data = [
                'payment_instructions' => $request->input('payment_instructions'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Use updateOrInsert to create or update the record based on company_id
            InvoicePaymentInstructionSetting::updateOrInsert(
                ['company_id' => auth('api')->user()->company_id],
                $data
            );

            // You can return a success response
            return response()->json([
                'status' => true,
                'message' => 'Payment instruction settings created or updated successfully',
            ]);
        } catch (QueryException $e) {
            // Handle database query exception
            Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to create or update payment instruction settings',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function invoicePaymentInstructionsIndex(Request $request)
    {
        // return $request;
        try {
            // Retrieve the authenticated user's company_id
            $companyId = Auth::guard('api')->user()->company_id;

            // Retrieve the payment instruction settings based on the company_id
            $paymentInstruction = InvoicePaymentInstructionSetting::where('company_id', $companyId)->first();

            if ($paymentInstruction) {
                // Return the payment instruction settings if found
                return response()->json([
                    'status' => true,
                    'data' => $paymentInstruction,
                ]);
            } else {
                // Return a not found response if no settings are found for the user's company
                return response()->json([
                    'status' => false,
                    'message' => 'Payment instruction settings not found for the company',
                ], 404);
            }
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */

    public function invoice_payment_instruction()
    {
        try {
            $working_hour = WorkingHour::where('company_settings_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'data' => $working_hour,
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

    public function getDisclaimer()
    {
        try {
            // Retrieve the authenticated user's company_id
            $companyId = Auth::guard('api')->user()->company_id;

            // Retrieve the disclaimer settings based on the company_id
            $disclaimer = InspectionReportDisclimer::where('company_id', $companyId)->first();

            if ($disclaimer) {
                // Return the disclaimer settings if found
                return response()->json([
                    'status' => true,
                    'data' => $disclaimer,
                ]);
            } else {
                // Return a not found response if no settings are found for the user's company
                return response()->json([
                    'status' => false,
                    'message' => 'Disclaimer settings not found for the company',
                ], 404);
            }
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createOrUpdateDisclaimer(Request $request)
    {
        try {
            // Retrieve the authenticated user's company_id
            $companyId = Auth::guard('api')->user()->company_id;

            // Find the disclaimer record based on the authenticated user's company_id
            $disclaimer = InspectionReportDisclimer::where('company_id', $companyId)->first();

            if (!$disclaimer) {
                // If no disclaimer record exists, create a new one
                $disclaimer = new InspectionReportDisclimer();
                $disclaimer->company_id = $companyId;
            }

            // Update the disclaimer record with the new data
            $disclaimer->entry_exit_inspection_reports = $request->input('entry_exit_inspection_reports');
            $disclaimer->routine_inspection_reports = $request->input('routine_inspection_reports');
            $disclaimer->save();

            return response()->json([
                'status' => true,
                'message' => $disclaimer->wasRecentlyCreated ? 'Disclaimer created successfully' : 'Disclaimer updated successfully',
                'data' => $disclaimer,
            ]);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
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
}
