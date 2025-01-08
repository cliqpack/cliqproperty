<?php

namespace Modules\Accounts\Http\Controllers\OwnerFolioSummary;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Accounts\Http\Controllers\TaxController;
use Modules\Properties\Entities\Properties;

class OwnerFinancialActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('accounts::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $taxAmount = 0;
            if ($request->taxChecker == 1) {
                $includeTax = new TaxController();
                $taxAmount = $includeTax->taxCalculation($request->amount);
            }

            if ($request->property_id == 'All') {
                $properties = Properties::select(['id', 'reference'])->where('owner_folio_id', $request->own_id)->where('company_id', auth('api')->user()->company_id)->get();
                DB::transaction(function () use ($properties, $request, $taxAmount) {
                    foreach ($properties as $value) {
                        $receiptDetails = new ReceiptDetails();
                        $receiptDetails->receipt_id = null;
                        $receiptDetails->allocation = null;
                        $receiptDetails->description = $request->ca_name;
                        $receiptDetails->folio_id = $request->own_id;
                        $receiptDetails->folio_type = "Owner";
                        $receiptDetails->amount = $request->amount;
                        $receiptDetails->tax = $request->taxChecker;
                        $receiptDetails->account_id = $request->ca_id;
                        $receiptDetails->payment_type = 'eft';
                        $receiptDetails->from_folio_id = null;
                        $receiptDetails->from_folio_type = null;
                        $receiptDetails->to_folio_id = $request->own_id;
                        $receiptDetails->to_folio_type = "Owner";
                        $receiptDetails->pay_type = $request->type;
                        $receiptDetails->type = $request->type == 'debit' ? "Withdraw" : "Deposit";
                        $receiptDetails->owner_folio_id = $request->own_id;
                        $receiptDetails->taxAmount = $taxAmount;
                        $receiptDetails->company_id = auth('api')->user()->company_id;
                        $receiptDetails->save();
                    }
                });
            } else {
                $receiptDetails = new ReceiptDetails();
                $receiptDetails->receipt_id = null;
                $receiptDetails->allocation = null;
                $receiptDetails->description = $request->ca_name;
                $receiptDetails->folio_id = $request->own_id;
                $receiptDetails->folio_type = "Owner";
                $receiptDetails->amount = $request->amount;
                $receiptDetails->tax = $request->taxChecker;
                $receiptDetails->account_id = $request->ca_id;
                $receiptDetails->payment_type = 'eft';
                $receiptDetails->from_folio_id = null;
                $receiptDetails->from_folio_type = null;
                $receiptDetails->to_folio_id = $request->own_id;
                $receiptDetails->to_folio_type = "Owner";
                $receiptDetails->pay_type = $request->type;
                $receiptDetails->type = $request->type == 'debit' ? "Withdraw" : "Deposit";
                $receiptDetails->owner_folio_id = $request->own_id;
                $receiptDetails->taxAmount = $taxAmount;
                $receiptDetails->company_id = auth('api')->user()->company_id;
                $receiptDetails->save();
            }

            return response()->json([
                'message' => 'Success',
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
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
            $receiptdetailsdata = ReceiptDetails::where('owner_folio_id', $id)
            ->whereNull('receipt_id')->with('account')->get();
            return response()->json([
                'message' => 'Success',
                'data' => $receiptdetailsdata
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('accounts::edit');
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
        try {
            ReceiptDetails::whereIn('id', $id)->delete();
            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroyMultiple(Request $request)
    {
        try {
            ReceiptDetails::whereIn('id', $request)->delete();
            return response()->json([
                'message' => 'Success'
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
