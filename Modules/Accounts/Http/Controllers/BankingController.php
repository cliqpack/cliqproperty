<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\BankDepositList;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\CurrentAllInOneBankDeposit;
use Modules\Accounts\Entities\CurrentAllInOneBankDepositList;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;

class BankingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('accounts::index');
    }

    /**
     * Retrieve the details of bank deposits, grouped by month, year, and payment method.
     * Returns a JSON response with the total amount of pending deposits for each group.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the grouped deposit details or an error response with exception details.
     */
    public function bankDepositListDetails()
    {
        try {
            $CashBankDetails = BankDepositList::selectRaw('sum(amount) as amount, MONTH(receipt_date) as month, YEAR(receipt_date) as year, payment_method')->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->groupByRaw('month, year, payment_method')->get();
            return response()->json(['data' => $CashBankDetails, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function bankDepositListDetailsAmount()
    {
        try {
            $CashBankDetailsAmount = BankDepositList::select('*')->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->sum('amount');
            return response()->json(['data' => $CashBankDetailsAmount, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve details of monthly deposits for a specific month and year.
     * Returns the list of deposit details along with a success message in a JSON response.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param int $month - The month to retrieve deposit details for.
     * @param int $year - The year to retrieve deposit details for.
     * @return \Illuminate\Http\JsonResponse - A successful response with the deposit details or an error response with exception details.
     */
    public function monthlyDepositListDetails($month, $year)
    {
        try {
            $monthlyDepositDetails = BankDepositList::where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->with('receipt', 'receipt.property')->get();
            return response()->json(['data' => $monthlyDepositDetails, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve the current list of all-in-one bank deposits for the authenticated user's company.
     * Returns a JSON response with the current list of bank deposits.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the current bank deposit list or an error response with exception details.
     */
    public function currentDepositListOneData()
    {
        try {
            $currentList = CurrentAllInOneBankDeposit::where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $currentList, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve details of a specific current bank deposit.
     * Returns the list of deposit details along with a success message in a JSON response.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param int $id - The ID of the current bank deposit list to retrieve details for.
     * @return \Illuminate\Http\JsonResponse - A successful response with the deposit details or an error response with exception details.
     */
    public function currentDepositData($id)
    {
        try {
            $currentList = CurrentAllInOneBankDepositList::where('deposit_list_id', $id)->where('company_id', auth('api')->user()->company_id)->with('receipt', 'receipt.property', 'receipt.ownerFolio', 'receipt.tenantFolio', 'receipt.supplierFolio', 'bank_deposit_list:id,deposit_date')->get();
            return response()->json(['data' => $currentList, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function unreconciledDepositListByMonth($month, $year)
    {
        try {
            $receipt = BankDepositList::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->where('status', 'Deposited')->where('reconcile', 'unreconciled')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $receipt, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function allDepositListByMonth($month, $year)
    {
        try {
            $bank = BankDepositList::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $bank, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
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
        //
    }

    /**
     * Deposit all pending bank deposits into the current bank deposit list.
     * This function performs a series of database operations within a transaction:
     * 1. Retrieves all pending bank deposit lists and calculates totals for different payment methods (Cash, Card, Cheque, Total).
     * 2. Creates a new entry in CurrentAllInOneBankDeposit to record the current deposit.
     * 3. Updates statuses in BankDepositList and Receipt tables and adjusts uncleared amounts in respective folio types (Owner, Supplier, Tenant, Seller).
     * 4. Creates entries in CurrentAllInOneBankDepositList to link deposits with the current deposit.
     * If any exception occurs during these operations, it rolls back the transaction and returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A JSON response indicating success or failure of the deposit operation.
     */
    public function depositAlllData()
    {
        try {
            $depositList = BankDepositList::where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->get();
            $cash = BankDepositList::where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->where('payment_method', 'Cash')->sum('amount');
            $card = BankDepositList::where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->where('payment_method', 'Card')->sum('amount');
            $cheque = BankDepositList::where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->where('payment_method', 'Cheque')->sum('amount');
            $total = BankDepositList::where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->sum('amount');
            DB::transaction(function () use ($depositList, $cash, $card, $cheque, $total) {
                $currentDeposit = new CurrentAllInOneBankDeposit();
                $currentDeposit->deposit_date = date('Y-m-d');
                $currentDeposit->cash = $cash;
                $currentDeposit->cheque = $cheque;
                $currentDeposit->card = $card;
                $currentDeposit->total = $total;
                $currentDeposit->company_id = auth('api')->user()->company_id;
                $currentDeposit->save();
                foreach ($depositList as $value) {
                    BankDepositList::where('id', $value->id)->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'Deposited',
                    ]);
                    Receipt::where('id', $value->receipt_id)->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'Cleared',
                        'cleared_date' => date('Y-m-d'),
                    ]);
                    $receiptDetails = ReceiptDetails::where('receipt_id', $value->receipt_id)->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($receiptDetails as $key => $val) {
                        if ($val->to_folio_type === 'Owner') {
                            $ownerFolio = OwnerFolio::where('id', $val->to_folio_id)->first();
                            OwnerFolio::where('id', $val->to_folio_id)->update([
                                'uncleared' => $ownerFolio->uncleared - $val->amount
                            ]);
                        } elseif ($val->to_folio_type === 'Supplier') {
                            $supplierFolio = SupplierDetails::where('id', $val->to_folio_id)->first();
                            SupplierDetails::where('id', $val->to_folio_id)->update([
                                'uncleared' => $supplierFolio->uncleared - $val->amount
                            ]);
                        } elseif ($val->to_folio_type === 'Tenant') {
                            $tenantFolio = TenantFolio::where('id', $val->to_folio_id)->first();
                            TenantFolio::where('id', $val->to_folio_id)->update([
                                'uncleared' => $tenantFolio->uncleared - $val->amount
                            ]);
                        } elseif ($val->to_folio_type === 'Seller') {
                            $tenantFolio = SellerFolio::where('id', $val->to_folio_id)->first();
                            SellerFolio::where('id', $val->to_folio_id)->update([
                                'uncleared' => $tenantFolio->uncleared - $val->amount
                            ]);
                        }
                    }
                    $currentDepositList = new CurrentAllInOneBankDepositList();
                    $currentDepositList->deposit_list_id = $currentDeposit->id;
                    $currentDepositList->b_id = $value->id;
                    $currentDepositList->receipt_id = $value->receipt_id;
                    $currentDepositList->company_id = auth('api')->user()->company_id;
                    $currentDepositList->save();
                }
            });
            return response()->json([
                'message' => 'All Current Bank List Deposited successfully'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Deposit selected bank deposit list items.
     * Updates the status of the selected deposit items to 'Deposited' and associated receipts to 'Cleared'.
     * Aggregates the amounts for each payment method (cash, card, cheque) and creates a new deposit record.
     * Returns a success message in a JSON response.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param \Illuminate\Http\Request $request - The request object containing the list of selected deposit item IDs.
     * @return \Illuminate\Http\JsonResponse - A successful response with a message or an error response with exception details.
     */
    public function depositSelectedData(Request $request)
    {
        try {
            $cash = 0;
            $card = 0;
            $cheque = 0;
            $total = 0;
            DB::transaction(function () use ($request, $cash, $card, $cheque, $total) {
                $receipt = array();
                foreach ($request->data as $value) {
                    $bankdepositdetails = BankDepositList::where('id', $value)->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->first();
                    if ($bankdepositdetails->payment_method === 'cash') {
                        $cash += $bankdepositdetails->amount;
                    } elseif ($bankdepositdetails->payment_method === 'card') {
                        $card += $bankdepositdetails->amount;
                    } else {
                        $cheque += $bankdepositdetails->amount;
                    }
                    $total += $bankdepositdetails->amount;
                    array_push($receipt, $bankdepositdetails->receipt_id);
                    BankDepositList::where('id', $value)->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'Deposited',
                    ]);
                    Receipt::where('id', $bankdepositdetails->receipt_id)->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'Cleared',
                        'cleared_date' => date('Y-m-d'),
                    ]);
                    $receiptDetails = ReceiptDetails::where('receipt_id', $bankdepositdetails->receipt_id)->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($receiptDetails as $key => $value) {
                        if ($value->to_folio_type === 'Owner') {
                            $ownerFolio = OwnerFolio::where('id', $value->to_folio_id)->first();
                            OwnerFolio::where('id', $value->to_folio_id)->update([
                                'uncleared' => $ownerFolio->uncleared - $value->amount
                            ]);
                        } elseif ($value->to_folio_type === 'Supplier') {
                            $supplierFolio = SupplierDetails::where('id', $value->to_folio_id)->first();
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'uncleared' => $supplierFolio->uncleared - $value->amount
                            ]);
                        } elseif ($value->to_folio_type === 'Tenant') {
                            $tenantFolio = TenantFolio::where('id', $value->to_folio_id)->first();
                            TenantFolio::where('id', $value->to_folio_id)->update([
                                'uncleared' => $tenantFolio->uncleared - $value->amount
                            ]);
                        } elseif ($value->to_folio_type === 'Seller') {
                            $tenantFolio = SellerFolio::where('id', $value->to_folio_id)->first();
                            SellerFolio::where('id', $value->to_folio_id)->update([
                                'uncleared' => $tenantFolio->uncleared - $value->amount
                            ]);
                        }
                    }
                }
                $currentDeposit = new CurrentAllInOneBankDeposit();
                $currentDeposit->deposit_date = date('Y-m-d');
                $currentDeposit->cash = $cash;
                $currentDeposit->cheque = $cheque;
                $currentDeposit->card = $card;
                $currentDeposit->total = $total;
                $currentDeposit->company_id = auth('api')->user()->company_id;
                $currentDeposit->save();
                foreach ($request->data as $key => $value) {
                    $currentDepositList = new CurrentAllInOneBankDepositList();
                    $currentDepositList->deposit_list_id = $currentDeposit->id;
                    $currentDepositList->b_id = $value;
                    $currentDepositList->receipt_id = $receipt[$key];
                    $currentDepositList->company_id = auth('api')->user()->company_id;
                    $currentDepositList->save();
                }
            });
            return response()->json([
                'message' => 'All Current Bank List Deposited successfully'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Cancel the last bank deposit based on the provided request data.
     * This function performs a series of database operations within a transaction:
     * 1. Deletes entries from the CurrentAllInOneBankDepositList table.
     * 2. Updates entries in the BankDepositList table to set status to 'Pending'.
     * 3. Updates entries in the Receipt table to set status to 'Uncleared' and cleared_date to NULL.
     * 4. Adjusts uncleared amounts in respective folio types (Owner, Supplier, Tenant).
     * 5. Deletes the CurrentAllInOneBankDeposit entry itself.
     * If any exception occurs during these operations, it rolls back the transaction and returns a 500 error response with the exception message.
     *
     * @param \Illuminate\Http\Request $request - The HTTP request containing the necessary data to cancel the deposit.
     * @return \Illuminate\Http\JsonResponse - A JSON response indicating success or failure of the cancellation process.
     */
    public function cancelLastDiposit(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->current_all_in_one_bank_deposit_list as $value) {
                    CurrentAllInOneBankDepositList::where('id', $value['id'])->delete();
                    BankDepositList::where('id', $value['b_id'])->update([
                        'status' => 'Pending',
                    ]);
                    Receipt::where('id', $value['receipt_id'])->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => 'Uncleared',
                        'cleared_date' => NULL,
                    ]);
                    $receiptDetails = ReceiptDetails::where('receipt_id', $value['receipt_id'])->where('company_id', auth('api')->user()->company_id)->get();
                    foreach ($receiptDetails as $key => $value) {
                        if ($value->to_folio_type === 'Owner') {
                            $ownerFolio = OwnerFolio::where('id', $value->to_folio_id)->first();
                            OwnerFolio::where('id', $value->to_folio_id)->update([
                                'uncleared' => $ownerFolio->uncleared + $value->amount,
                            ]);
                        } elseif ($value->to_folio_type === 'Supplier') {
                            $supplierFolio = SupplierDetails::where('id', $value->to_folio_id)->first();
                            SupplierDetails::where('id', $value->to_folio_id)->update([
                                'uncleared' => $supplierFolio->uncleared + $value->amount
                            ]);
                        } elseif ($value->to_folio_type === 'Tenant') {
                            $tenantFolio = TenantFolio::where('id', $value->to_folio_id)->first();
                            TenantFolio::where('id', $value->to_folio_id)->update([
                                'uncleared' => $tenantFolio->uncleared + $value->amount
                            ]);
                        }
                    }
                }
                CurrentAllInOneBankDeposit::where('id', $request->id)->delete();
            });
            return response()->json([
                'message' => 'Last deposit cancelled'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Retrieve the last deposit record for the authenticated user's company.
     * Returns a JSON response with the last deposit details.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @return \Illuminate\Http\JsonResponse - A successful response with the last deposit details or an error response with exception details.
     */
    public function getLastDiposit()
    {
        try {
            $current_all_in_one_bank_deposits = CurrentAllInOneBankDeposit::where('company_id', auth('api')->user()->company_id)->with('CurrentAllInOneBankDepositList')->orderBy('id', 'DESC')->first();
            return response()->json([
                'current' => $current_all_in_one_bank_deposits,
                'message' => 'Cancel Last Deposit successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('accounts::show');
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
        //
    }
}
