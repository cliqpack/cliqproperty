<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Http\Controllers\DocumentGenerateController;
use Modules\Accounts\Http\Controllers\TaxController;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Settings\Entities\CompanySetting;
use Modules\UserACL\Entities\OwnerPlan;
use Modules\UserACL\Entities\OwnerPlanDetails;

class OwnerPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $ownerPlan = OwnerPlan::with('plan', 'owner')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'user_plan' => $ownerPlan,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
        return view('useracl::create');
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
                'plan' => $request->plan,
                'user' => $request->user,
            );
            $validator = Validator::make($attributeNames, [
                'plan' => 'required',
                'user' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $menuPlanDetails = new OwnerPlan();
                $findMp = $menuPlanDetails->where('user_id', $request->user)->where('company_id', auth('api')->user()->company_id)->get();

                if (count($findMp) == 0) {
                    $menuPlanDetails->menu_plan_id  = $request->plan;
                    $menuPlanDetails->user_id       = $request->user;
                    $menuPlanDetails->company_id    = auth('api')->user()->company_id;
                    $menuPlanDetails->save();
                } else {
                    OwnerPlan::where("id", $findMp[0]->id)->update([
                        "menu_plan_id"     => $request->plan,
                    ]);
                }
                return response()->json([
                    'message' => 'successful',
                    'menu_plan_details_id' => $menuPlanDetails->id,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
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
    public function show(Request $request, $id)
    {
        try {
            $ownerPlan = OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->where('company_id', auth('api')->user()->company_id)->with('untriggeredOwnerPlanDetails', function ($q) {
                $q->where('status', false);
            })->first();
            return response()->json([
                'status' => 'Success',
                'ownerPlan' => $ownerPlan
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "Failed",
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
        return view('useracl::edit');
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

    /**
     * THIS FUNCTION USED TO CREATE BILL
     */
    public function createBill($supplier, $property_id, $price, $ownerfolioid, $company_id, $planName)
    {
        // $taxAmount = 0;
        // $coa = Account::where('id', $addon->account_id)->where('company_id', $company_id)->first();
        // if ($coa->tax == true) {
        //     $includeTax = new TaxController();
        //     $taxAmount = $includeTax->taxCalculation($price);
        // }

        $approved = false;
        $company_settings = CompanySetting::where('company_id', $company_id)->first();
        $supplierDetails = SupplierDetails::where('id', $supplier->id)->where('company_id', $company_id)->first();
        $bill = new Bill();
        $bill->supplier_contact_id      = $supplier->supplier_contact_id;
        $bill->billing_date             = date('Y-m-d');
        $bill->bill_account_id          = NULL;
        $bill->invoice_ref              = '';
        $bill->property_id              = $property_id;
        $bill->amount                   = $price;
        $bill->priority                 = '';
        $bill->details                  = "System generated bill by " . $planName . " plan";
        $bill->maintenance_id           = NULL;
        $bill->include_tax              = 1;
        $bill->company_id               = $company_id;
        $bill->supplier_folio_id        = $supplier->id;
        $bill->owner_folio_id           = $ownerfolioid;

        if ($company_settings->bill_approval === 1) {
            if (!empty($supplierDetails) && $supplierDetails->auto_approve_bills === 1) {
                $bill->approved = true;
                $approved = true;
            } else {
                $bill->approved = false;
            }
        } elseif ($company_settings->bill_approval === 0) {
            $bill->approved = true;
        }
        $bill->save();


        $bill = Bill::where('id', $bill->id)
            ->where('company_id', $company_id)
            ->with('property', 'property.property_address', 'ownerFolio.ownerContacts')
            ->first();
        $propAddress = '';
        if ($bill->property) {
            $propAddress = $bill->property->property_address->number . ' ' . $bill->property->property_address->street . ' ' . $bill->property->property_address->suburb . ' ' . $bill->property->property_address->state . ' ' . $bill->property->property_address->postcode;
        }

        $data = [
            'taxAmount' => 0.00,
            'propAddress' => $propAddress,
            'bill_id' => $bill->id,
            'owner_folio' => $bill->ownerFolio->folio_code,
            'owner_name' => $bill->ownerFolio->ownerContacts->reference,
            'created_date' => $bill->billing_date,
            'due_date' => $bill->billing_date,
            'amount' => $bill->amount,
            'description' => $bill->details,
            'property_id' => $bill->property_id,
            'to' => $bill->ownerFolio->ownerContacts->email,
            'approved' => $approved,
        ];
        $triggerDoc = new DocumentGenerateController();
        $triggerDoc->generateBill($data);
        return $bill->id;
    }
    /**
     * THIS FUNCTION USED TO TRIGGER BILL BASED ON PLAN AMOUNT
     */
    public function triggerPlan()
    {
        try {
            $db = DB::transaction(function () {
                $company = Company::all();
                foreach ($company as $item) {
                    $supplier = SupplierDetails::where('company_id', $item->id)->where('system_folio', 1)->first();
                    $monthlyDate = date('d');
                    $monthlyDate = (int) $monthlyDate;
                    $monthNumber = date('m');
                    $monthNumber = (int) $monthNumber;
                    $time = Carbon::now()->format('H:i');
                    $today = Carbon::now()->format('Y-m-d');
                    $ownerPlans = OwnerPlan::where('company_id', $item->id)->with('untriggeredOwnerPlanDetails', function ($q) {
                        $q->where('status', false);
                    })->with('plan', 'owner', 'owner.multipleOwnerFolios:id,owner_contact_id,property_id', 'property')->get();
                    foreach ($ownerPlans as $value) {
                        $price = $value->plan->price;
                        $planName = $value->plan->name;
                        $property_id = $value->property->id;
                        $ownerfolioid = $value->property->owner_folio_id;
                        if ($value->plan->frequency_type === 'Weekly') {
                            if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                                $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, $item->id, $planName);
                                OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                    'status' => true,
                                    'bill_id' => $billId,
                                ]);
                                $ownerPlanDetails = new OwnerPlanDetails();
                                $ownerPlanDetails->owner_plan_id = $value->id;
                                $ownerPlanDetails->company_id = $item->id;
                                $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                                $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                                $ownerPlanDetails->trigger_date = Carbon::parse('next ' . $value->untriggeredOwnerPlanDetails->weekly)->toDateString();
                                $ownerPlanDetails->weekly = $value->untriggeredOwnerPlanDetails->weekly;
                                $ownerPlanDetails->save();
                            }
                        } elseif ($value->plan->frequency_type === 'FortNightly') {
                            if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                                $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, $item->id, $planName);
                                OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                    'status' => true,
                                    'bill_id' => $billId,
                                ]);
                                $ownerPlanDetails = new OwnerPlanDetails();
                                $ownerPlanDetails->owner_plan_id = $value->id;
                                $ownerPlanDetails->company_id = $item->id;
                                $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                                $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                                $ownerPlanDetails->trigger_date = (new Carbon($today))->addWeeks(2)->format('Y-m-d');
                                $ownerPlanDetails->fortnightly = (new Carbon($today))->addWeeks(2)->format('Y-m-d');
                                $ownerPlanDetails->save();
                            }
                        } elseif ($value->plan->frequency_type === 'Monthly') {
                            if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                                $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, $item->id, $planName);
                                OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                    'status' => true,
                                    'bill_id' => $billId,
                                ]);
                                $ownerPlanDetails = new OwnerPlanDetails();
                                $ownerPlanDetails->owner_plan_id = $value->id;
                                $ownerPlanDetails->company_id = $item->id;
                                $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                                $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                                $ownerPlanDetails->trigger_date = (new Carbon($today))->addMonth(1)->format('Y-m-d');
                                $ownerPlanDetails->monthly = $value->untriggeredOwnerPlanDetails->monthly;
                                $ownerPlanDetails->save();
                            }
                        } elseif ($value->plan->frequency_type === 'Yearly') {
                            $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, $item->id, $planName);
                            OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                'status' => true,
                                'bill_id' => $billId,
                            ]);
                            $ownerPlanDetails = new OwnerPlanDetails();
                            $ownerPlanDetails->owner_plan_id = $value->id;
                            $ownerPlanDetails->company_id = $item->id;
                            $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                            $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                            $ownerPlanDetails->trigger_date = (new Carbon($today))->addYear(1)->format('Y-m-d');
                            $ownerPlanDetails->yearly = $value->untriggeredOwnerPlanDetails->yearly;
                            $ownerPlanDetails->save();
                        }
                    }
                }
                return response()->json(['status' => 'Success'], 200);
            });
            return $db;
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "Failed",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function triggerCompanyPlan()
    {
        try {
            $db = DB::transaction(function () {
                $supplier = SupplierDetails::where('company_id', auth('api')->user()->company_id)->where('system_folio', 1)->first();
                $monthlyDate = date('d');
                $monthlyDate = (int) $monthlyDate;
                $monthNumber = date('m');
                $monthNumber = (int) $monthNumber;
                $time = Carbon::now()->format('H:i');
                $today = Carbon::now()->format('Y-m-d');
                $ownerPlans = OwnerPlan::where('company_id', auth('api')->user()->company_id)->with('untriggeredOwnerPlanDetails', function ($q) {
                    $q->where('status', false);
                })->with('plan', 'owner', 'owner.multipleOwnerFolios:id,owner_contact_id,property_id', 'property')->get();
                foreach ($ownerPlans as $value) {
                    $price = $value->plan->price;
                    $planName = $value->plan->name;
                    $property_id = $value->property->id;
                    $ownerfolioid = $value->property->owner_folio_id;
                    if ($value->plan->frequency_type === 'Weekly') {
                        if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                            $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, auth('api')->user()->company_id, $planName);
                            OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                'status' => true,
                                'bill_id' => $billId,
                            ]);
                            $ownerPlanDetails = new OwnerPlanDetails();
                            $ownerPlanDetails->owner_plan_id = $value->id;
                            $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                            $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                            $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                            $ownerPlanDetails->trigger_date = Carbon::parse('next ' . $value->untriggeredOwnerPlanDetails->weekly)->toDateString();
                            $ownerPlanDetails->weekly = $value->untriggeredOwnerPlanDetails->weekly;
                            $ownerPlanDetails->save();
                        }
                    } elseif ($value->plan->frequency_type === 'FortNightly') {
                        if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                            $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, auth('api')->user()->company_id, $planName);
                            OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                'status' => true,
                                'bill_id' => $billId,
                            ]);
                            $ownerPlanDetails = new OwnerPlanDetails();
                            $ownerPlanDetails->owner_plan_id = $value->id;
                            $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                            $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                            $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                            $ownerPlanDetails->trigger_date = (new Carbon($today))->addWeeks(2)->format('Y-m-d');
                            $ownerPlanDetails->fortnightly = (new Carbon($today))->addWeeks(2)->format('Y-m-d');
                            $ownerPlanDetails->save();
                        }
                    } elseif ($value->plan->frequency_type === 'Monthly') {
                        if ($value->untriggeredOwnerPlanDetails->trigger_date === $today && $value->untriggeredOwnerPlanDetails->trigger_time == $time) {
                            $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, auth('api')->user()->company_id, $planName);
                            OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                                'status' => true,
                                'bill_id' => $billId,
                            ]);
                            $ownerPlanDetails = new OwnerPlanDetails();
                            $ownerPlanDetails->owner_plan_id = $value->id;
                            $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                            $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                            $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                            $ownerPlanDetails->trigger_date = (new Carbon($today))->addMonth(1)->format('Y-m-d');
                            $ownerPlanDetails->monthly = $value->untriggeredOwnerPlanDetails->monthly;
                            $ownerPlanDetails->save();
                        }
                    } elseif ($value->plan->frequency_type === 'Yearly') {
                        $billId = $this->createBill($supplier, $property_id, $price, $ownerfolioid, auth('api')->user()->company_id, $planName);
                        OwnerPlanDetails::where('id', $value->untriggeredOwnerPlanDetails->id)->update([
                            'status' => true,
                            'bill_id' => $billId,
                        ]);
                        $ownerPlanDetails = new OwnerPlanDetails();
                        $ownerPlanDetails->owner_plan_id = $value->id;
                        $ownerPlanDetails->company_id = auth('api')->user()->company_id;
                        $ownerPlanDetails->frequency_type = $value->plan->frequency_type;
                        $ownerPlanDetails->trigger_time = $value->untriggeredOwnerPlanDetails->trigger_time;
                        $ownerPlanDetails->trigger_date = (new Carbon($today))->addYear(1)->format('Y-m-d');
                        $ownerPlanDetails->yearly = $value->untriggeredOwnerPlanDetails->yearly;
                        $ownerPlanDetails->save();
                    }
                }
                return response()->json(['status' => 'Success'], 200);
            });
            return $db;
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "Failed",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    /**
     * THIS FUNCTION IS USED TO GET A PLAN SCHEDULE
     * @param
     * IT TAKES OWNER_CONTACT_ID AS ARGUMENT
     */
    public function getPlanSchedule(Request $request, $id, $planId)
    {
        try {
            $getPlanSchedule = OwnerPlan::where('owner_id', $id)->where('property_id', $request->proId)->where('menu_plan_id', $planId)->where('company_id', auth('api')->user()->company_id)->with('untriggeredOwnerPlanDetails')->first();
            return response()->json([
                'status' => 'Success',
                'data' => $getPlanSchedule,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "Failed",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
