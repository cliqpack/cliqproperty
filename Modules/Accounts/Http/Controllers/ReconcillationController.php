<?php

namespace Modules\Accounts\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\ReconcilliationMonths;
use Modules\Accounts\Entities\ReconcilliationMonthsDetails;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\BankDepositList;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Accounts\Entities\GeneratedWithdrawal;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\RMonthsDetailsAdjustment;
use Modules\Accounts\Entities\Withdrawal;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\CurrentAllInOneBankDeposit;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;

class ReconcillationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function reconcillationList()
    {
        try {
            $withdrawals_not_processed = Withdrawal::where('status', false)->where('company_id', auth('api')->user()->company_id)->count();
            $reconcillationList = ReconcilliationMonths::select('*')->where('company_id', auth('api')->user()->company_id)->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $reconcillationList, 'totalWithdraw' => $withdrawals_not_processed, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    // public function reconcillationListDetails(Request $request)
    // {

    //     try {
    //         $today = Date('Y-m');
    //         $reconcillationList = ReconcilliationMonthsDetails::where('r_month_id', $request->id)->where('company_id', auth('api')->user()->company_id)->with('reconcilliationMonth')->first();
    //         $ledgerBalance = FolioLedger::where('company_id', auth('api')->user()->company_id)->where('date', 'LIKE', '%'.$today.'%')->sum('closing_balance');
    //         return response()->json(
    //             [
    //                 'data' => $reconcillationList,
    //                 'ledgerBalance' => $ledgerBalance,
    //                 'message' => 'Successful'
    //             ],
    //             200
    //         );
    //     } catch (\Exception $ex) {
    //         return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
    //     }
    // }

    public function reconcillationListDetails(Request $request)
    {
        // return $request;

        try {
            $today = Date('Y-m');
            $reconMonth = ReconcilliationMonths::where('id', $request->id)->where('company_id', auth()->user()->company_id)->first();

            if ($reconMonth) {
                $today = Carbon::parse($reconMonth->date)->format('Y-m');
                // return $date;
            }
            // return $today;
            $reconcillationList = ReconcilliationMonthsDetails::where('r_month_id', $request->id)->where('company_id', auth('api')->user()->company_id)->with('reconcilliationMonth')->first();
            $ledgerBalance = FolioLedgerDetailsDaily::where('company_id', auth('api')->user()->company_id)->where('date', 'LIKE', '%'.$today.'%')->sum('amount');
            // return $ledgerBalance;
            return response()->json(
                [
                    'data' => $reconcillationList,
                    'ledgerBalance' => $ledgerBalance,
                    'message' => 'Successful'
                ],
                200
            );
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // ADJUSTMENT LIST
    public function adjustmentList($id)
    {
        try {
            $adjustmentList = RMonthsDetailsAdjustment::where('r_month_details_id', $id)->where('removed', 0)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $adjustmentList, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // ALL ADJUSTMENT LIST
    public function allAdjustmentList($id)
    {
        try {
            $allAdjustmentList = RMonthsDetailsAdjustment::where('r_month_details_id', $id)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $allAdjustmentList, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // UNRECONCILED ITEMS
    public function unreconciledItems($month, $year, $id)
    {
        try {
            $adjustmentList = RMonthsDetailsAdjustment::where('adjustment_date', 'Like', '%' . $year . '-' . $month . '%')->where('r_month_details_id', $id)->where('removed', 0)->where('company_id', auth('api')->user()->company_id)->get();
            $removedAdjustmentList = RMonthsDetailsAdjustment::where('adjustment_date', 'Like', '%' . $year . '-' . $month . '%')->where('r_month_details_id', $id)->where('removed', 1)->where('company_id', auth('api')->user()->company_id)->get();
            $receipt = BankDepositList::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->where('status', 'Deposited')->where('reconcile', 'unreconciled')->where('company_id', auth('api')->user()->company_id)->get();
            $unreconcilliedWithdrawl = GeneratedWithdrawal::where('reconcile', false)->where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $year . '-' . $month . '%')->get();
            $unreconcilliedWithdrawlAmount = GeneratedWithdrawal::select('*')->where('reconcile', false)->where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $year . '-' . $month . '%')->sum('amount');
            return response()->json([
                'adjustmentList' => $adjustmentList,
                'removedAdjustmentList' => $removedAdjustmentList,
                'receipt' => $receipt,
                'unreconcilliedWithdrawl' => $unreconcilliedWithdrawl,
                'unreconcilliedWithdrawlAmount' => $unreconcilliedWithdrawlAmount,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // STORE ADJUSTMENT
    public function storeAdjustment(Request $request)
    {
        try {
            $attributeNames = array(
                'reason'    => $request->chart_of_account_id,
                'amount'    => $request->amount,
            );
            Validator::make($attributeNames, [
                'reason' => 'required',
                'amount' => 'required',
            ]);
            $adjustedAmount = round($request->amount, 2);
            $adjustment = new RMonthsDetailsAdjustment();
            $adjustment->r_month_details_id = $request->r_month_details_id;
            $adjustment->adjustment_date    = $request->adjustment_date ? $request->adjustment_date : date('Y-m-d');
            $adjustment->reason             = $request->reason;
            $adjustment->amount             = $adjustedAmount;
            $adjustment->company_id         = auth('api')->user()->company_id;
            $adjustment->save();
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    // REMOVE ADJUSTMENT
    public function removeAdjustment(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->removedata as $key => $value) {
                    RMonthsDetailsAdjustment::where('id', $value['id'])->update([
                        'removed' => true,
                        'removed_reason' => $request->removed_reason,
                        'remove_date' => date('Y-m-d')
                    ]);
                }
            });
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function reconcilliation_store()
    {
        try {
            DB::transaction(function () {
                $today = Date('Y-m-d');
                $reconMonth = ReconcilliationMonths::wheremonth('date', Carbon::parse(now())->format('m'))->whereYear('date', Carbon::parse(now())->format('Y'))->where('company_id', auth()->user()->company_id)->get();
                $count = count($reconMonth);
                if ($count == 0) {
                    $prevMonthhh = Carbon::now()->startOfMonth()->subMonth()->format('Y-m');
                    $enddate = Carbon::createFromFormat('Y-m-d', $today)->endOfMonth()->format('Y-m-d');
                    $prevReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $prevMonthhh . '%')->where('company_id', auth()->user()->company_id)->first();
                    $reconMonth = new ReconcilliationMonths();
                    $reconMonth->date        = $today;
                    $reconMonth->summary     = "reconMonth summary";
                    $reconMonth->status      = 0;
                    $reconMonth->enddate      = $enddate;
                    $reconMonth->current_date      = $today;
                    if (empty($prevReconMonth)) {
                        $reconMonth->reconciliation_status = 'approve';
                    } else {
                        if ($prevReconMonth->reconciliation_status === 'pending') {
                            $reconMonth->reconciliation_status = 'pending';
                        } elseif ($prevReconMonth->reconciliation_status === 'approve') {
                            $reconMonth->reconciliation_status = 'pending';
                        } elseif ($prevReconMonth->reconciliation_status === 'closed') {
                            $reconMonth->reconciliation_status = 'approve';
                        }
                    }
                    $reconMonth->company_id  = auth('api')->user()->company_id;
                    $reconMonth->save();

                    $date = Date('Y-m');
                    $receipt = Receipt::where('receipt_date', 'LIKE', '%' . $date . '%')->whereIn('type', ['Tenant Receipt', 'Receipt', 'Folio Receipt', 'Invoice'])->where('reversed', false)->where('company_id', auth('api')->user()->company_id)->get();
                    $newReceipt = $receipt->sum('amount');
                    $bankDepositeList = BankDepositList::where('receipt_date', 'LIKE', '%' . $date . '%')->where('company_id', auth('api')->user()->company_id)->get();
                    $BankDeposite = $bankDepositeList->where('status', 'Deposited')->where('reconcile', 'unreconciled')->where('company_id', auth('api')->user()->company_id)->sum('amount');
                    $notBankDeposite = $bankDepositeList->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->sum('amount');
                    $unreconcillied_withdrawls = GeneratedWithdrawal::where('reconcile', false)->where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $date . '%')->sum('amount');
                    $withdrawls = Receipt::select('amount', 'receipt_date')->where('receipt_date', 'LIKE', '%' . $date . '%')->whereIn('type', ['Folio Withdraw', 'Withdraw'])->where('reversed', false)->where('company_id', auth('api')->user()->company_id)->get();
                    $new_withdrawls = $withdrawls->sum('amount');
                    $withdrawals_not_processed = Withdrawal::select('*')->where('status', false)->where('company_id', auth('api')->user()->company_id)->sum('amount');
                    $MonthsDetailsAdjustment = RMonthsDetailsAdjustment::where('adjustment_date', 'LIKE', '%' . $date . '%')->where('company_id', auth('api')->user()->company_id)->where('removed', 0)->get();
                    $adjustment = $MonthsDetailsAdjustment->sum('amount');

                    $now = Carbon::now();
                    $prevMonth = $now->subMonth()->format('Y-m');
                    $reconPrevMonth = ReconcilliationMonths::where('company_id', auth('api')->user()->company_id)->where('date', 'LIKE', '%' . $prevMonth . '%')->with('monthDetails')->first();

                    $prev_cash_amount = 0;
                    $prev_new_receipts = 0;
                    $prev_new_withdrawals = 0;
                    if ($reconPrevMonth) {
                        $prev_cash_amount = $reconPrevMonth->monthDetails->cashbook_amount;
                        $prev_new_receipts = $reconPrevMonth->monthDetails->new_receipts;
                        $prev_new_withdrawals = $reconPrevMonth->monthDetails->new_withdrawals;
                    }
                    $cashbook_amount = $prev_cash_amount + $prev_new_receipts - $prev_new_withdrawals;
                    $journal_balance = $cashbook_amount + $newReceipt - $new_withdrawls;
                    $reconMonthDetails = new ReconcilliationMonthsDetails();
                    $reconMonthDetails->r_month_id = $reconMonth->id;
                    $reconMonthDetails->unreconciled_deposits = $BankDeposite;
                    $reconMonthDetails->unreconciled_withdrawals = $unreconcillied_withdrawls;
                    $reconMonthDetails->adjustment = $adjustment;
                    $reconMonthDetails->cash_not_banked = $notBankDeposite;
                    $reconMonthDetails->withdrawals_not_processed = $withdrawals_not_processed;
                    $reconMonthDetails->new_receipts = $newReceipt;
                    $reconMonthDetails->new_withdrawals = $new_withdrawls;
                    $reconMonthDetails->cashbook_amount = $cashbook_amount;
                    $reconMonthDetails->journal_balance = $journal_balance;
                    $reconMonthDetails->company_id = auth('api')->user()->company_id;
                    $reconMonthDetails->status = 1;
                    $reconMonthDetails->save();
                }
            });

            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function reconcilliation($id)
    {
        try {
            $db = DB::transaction(function () use ($id) {
                $reconMonth = ReconcilliationMonths::where('id', $id)->where('company_id', auth()->user()->company_id)->first();
                $newDate = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->format('Y-m');

                $prevMonth = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->startOfMonth()->subMonth()->format('Y-m');
                $prevReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $prevMonth . '%')->where('company_id', auth()->user()->company_id)->first();

                $nextMonth = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->startOfMonth()->addMonth()->format('Y-m');
                $nextReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $nextMonth . '%')->where('company_id', auth()->user()->company_id)->first();

                $receipt = Receipt::select('amount', 'receipt_date')->where('receipt_date', 'LIKE', '%' . $newDate . '%')->whereIn('type', ['Tenant Receipt', 'Receipt', 'Folio Receipt', 'Invoice'])->where('reversed', false)->where('company_id', auth('api')->user()->company_id)->get();
                $newReceipt = $receipt->sum('amount');

                $bankDepositeList = BankDepositList::where('receipt_date', 'LIKE', '%' . $newDate . '%')->where('company_id', auth('api')->user()->company_id)->get();

                $BankDeposite = $bankDepositeList->where('status', 'Deposited')->where('reconcile', 'unreconciled')->where('company_id', auth('api')->user()->company_id)->sum('amount');
                $notBankDeposite = $bankDepositeList->where('status', 'Pending')->where('company_id', auth('api')->user()->company_id)->sum('amount');

                $withdrawls = Receipt::select('amount', 'receipt_date')->where('receipt_date', 'LIKE', '%' . $newDate . '%')->whereIn('type', ['Folio Withdraw', 'Withdraw'])->where('reversed', false)->where('company_id', auth('api')->user()->company_id)->get();
                $new_withdrawls = $withdrawls->sum('amount');
                $unreconcillied_withdrawls = GeneratedWithdrawal::where('reconcile', false)->where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $newDate . '%')->sum('amount');
                $withdrawals_not_processed = Withdrawal::select('*')->where('create_date', 'LIKE', '%' . $newDate . '%')->where('status', false)->where('company_id', auth('api')->user()->company_id)->sum('amount');

                $MonthsDetailsAdjustment = RMonthsDetailsAdjustment::where('adjustment_date', 'LIKE', '%' . $newDate . '%')->where('removed', 0)->where('company_id', auth('api')->user()->company_id)->get();
                $adjustment = $MonthsDetailsAdjustment->sum('amount');
                $reconMonth = ReconcilliationMonths::where('id', $id)->where('company_id', auth('api')->user()->company_id)->first();
                if (empty($prevReconMonth) && $reconMonth->reconciliation_status === 'pending') {
                    $reconMonth->reconciliation_status = 'approve';
                } elseif (!empty($prevReconMonth)) {
                    if ($prevReconMonth->reconciliation_status === 'pending') {
                        $reconMonth->reconciliation_status = 'pending';
                    } elseif ($prevReconMonth->reconciliation_status === 'approve') {
                        $reconMonth->reconciliation_status = 'pending';
                    } elseif ($prevReconMonth->reconciliation_status === 'closed' && $reconMonth->reconciliation_status === 'pending') {
                        $reconMonth->reconciliation_status = 'approve';
                    }
                }

                if (empty($nextReconMonth)) {
                } elseif (!empty($nextReconMonth)) {
                    if ($reconMonth->reconciliation_status === 'pending') {
                        $nextReconMonth->reconciliation_status = 'pending';
                    } elseif ($reconMonth->reconciliation_status === 'approve') {
                        $nextReconMonth->reconciliation_status = 'pending';
                    } elseif ($reconMonth->reconciliation_status === 'approved') {
                        $nextReconMonth->reconciliation_status = 'approve';
                    }
                    $nextReconMonth->save();
                }
                $reconMonth->status = 1;
                $reconMonth->save();


                $reconMonthDetails = ReconcilliationMonthsDetails::where('r_month_id', $id)->first();
                $net_bank_balance = $reconMonthDetails->bank_statement_balance + $BankDeposite - $unreconcillied_withdrawls + $adjustment + $notBankDeposite - $withdrawals_not_processed;
                $journal_balance = $reconMonthDetails->cashbook_amount + $newReceipt - $new_withdrawls;
                $reconMonthDetails->unreconciled_deposits = $BankDeposite;
                $reconMonthDetails->unreconciled_withdrawals = $unreconcillied_withdrawls;
                $reconMonthDetails->adjustment = $adjustment;
                $reconMonthDetails->cash_not_banked = $notBankDeposite;
                $reconMonthDetails->withdrawals_not_processed = $withdrawals_not_processed;
                $reconMonthDetails->new_receipts = $newReceipt;
                $reconMonthDetails->new_withdrawals = $new_withdrawls;
                $reconMonthDetails->net_bank_balance = $net_bank_balance;
                $reconMonthDetails->journal_balance = $journal_balance;
                $reconMonthDetails->company_id = auth('api')->user()->company_id;
                $reconMonthDetails->status = 1;
                $reconMonthDetails->save();
                return response()->json(['message' => 'Successfull'], 200);
            });
            return $db;
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function approveReconciliation($id)
    {
        try {
            $db = DB::transaction(function () use ($id) {
                $reconMonth = ReconcilliationMonths::where('id', $id)->where('company_id', auth()->user()->company_id)->first();
                $prevMonth = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->startOfMonth()->subMonth()->format('Y-m');
                $nextMonth = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->startOfMonth()->addMonth()->format('Y-m');
                $prevReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $prevMonth . '%')->where('company_id', auth()->user()->company_id)->first();
                $nextReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $nextMonth . '%')->where('company_id', auth()->user()->company_id)->first();

                $reconMonth->status = 1;
                $reconMonth->reconciliation_status = 'approved';
                $reconMonth->save();

                if (!empty($prevReconMonth)) {
                    $prevReconMonth->status = 1;
                    $prevReconMonth->reconciliation_status = 'closed';
                    $prevReconMonth->save();
                }
                if (!empty($nextReconMonth)) {
                    $nextReconMonth->status = 1;
                    $nextReconMonth->reconciliation_status = 'approve';
                    $nextReconMonth->save();
                }
                return response()->json(['message' => 'Successfull'], 200);
            });
            return $db;
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function revokeReconciliation($id)
    {
        try {
            $db = DB::transaction(function () use ($id) {
                $reconMonth = ReconcilliationMonths::where('id', $id)->where('company_id', auth()->user()->company_id)->first();
                $prevMonth = Carbon::createFromFormat('Y-m-d', $reconMonth->date)->startOfMonth()->subMonth()->format('Y-m');
                $prevReconMonth = ReconcilliationMonths::where('date', 'LIKE', '%' . $prevMonth . '%')->where('company_id', auth()->user()->company_id)->first();

                $reconMonth->status = 1;
                $reconMonth->reconciliation_status = 'approve';
                $reconMonth->save();

                if (!empty($prevReconMonth)) {
                    $prevReconMonth->status = 1;
                    $prevReconMonth->reconciliation_status = 'approved';
                    $prevReconMonth->save();
                }
                return response()->json(['message' => 'Successfull'], 200);
            });
            return $db;
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function unreconcillied_withdrawls($year, $month)
    {
        try {
            $unreconcilliedWithdrawl = GeneratedWithdrawal::where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $year . '-' . $month . '%')->get();
            return response()->json(['message' => 'Successfull', 'data' => $unreconcilliedWithdrawl], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function all_reconcillied($year, $month)
    {
        try {
            $unreconcilliedWithdrawl = GeneratedWithdrawal::where('reconcile', false)->where('company_id', auth('api')->user()->company_id)->where('create_date', 'LIKE', '%' . $year . '-' . $month . '%')->get();
            return response()->json(['message' => 'Successfull', 'data' => $unreconcilliedWithdrawl], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function unreconcillied_withdrawls_update(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->id as $value) {
                    GeneratedWithdrawal::where('id', $value)->where('reconcile', false)->update([
                        "reconcile" => 1,
                        "reconcile_date" => Date('Y-m-d')
                    ]);
                }
            });
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function reconcillied_withdrawls_update(Request $request)
    {
        try {
            foreach ($request->id as $key => $value) {
                DB::transaction(function () use ($request) {
                    foreach ($request->id as $value) {
                        GeneratedWithdrawal::where('id', $value)->update([
                            "reconcile" => false,
                            "reconcile_date" => NULL
                        ]);
                    }
                });
            }
            return response()->json(['message' => 'Successfull'], 200);
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
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

    // RECONCILE BANK DEPOSIT LIST
    public function reconcileDepositData(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $value) {
                    BankDepositList::where('id', $value)->where('reconcile', 'unreconciled')->where('company_id', auth('api')->user()->company_id)->update([
                        'reconcile' => 'reconciled',
                        'reconcile_date' => date('Y-m-d'),
                    ]);
                }
            });
            return response()->json([
                'message' => 'Reconciled'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    // UNRECONCILE BANK DEPOSIT LIST
    public function unReconcileDepositData(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $value) {
                    BankDepositList::where('id', $value)->where('reconcile', 'reconciled')->where('company_id', auth('api')->user()->company_id)->update([
                        'reconcile' => 'unreconciled',
                        'reconcile_date' => NULL,
                    ]);
                }
            });
            return response()->json([
                'message' => 'Reconciled'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    //Bank Statement Balance
    public function bankStatementBalance(Request $request, $id)
    {
        try {
            $bankStatementBalance = round($request->bank_statement_balance, 2);
            $bankStatement = ReconcilliationMonthsDetails::where('r_month_id', $id)->where('company_id', auth('api')->user()->company_id)->first();
            $net_bank_balance = $bankStatementBalance + $bankStatement->unreconciled_deposits - $bankStatement->unreconciled_withdrawals + $bankStatement->adjustment + $bankStatement->cash_not_banked - $bankStatement->withdrawals_not_processed;
            $bankStatement->bank_statement_balance = $bankStatementBalance;
            $bankStatement->bank_statement_balance_date = $request->bank_statement_balance_date;
            $bankStatement->net_bank_balance = $net_bank_balance;
            $bankStatement->save();
            return response()->json([
                "data" => $bankStatement,
                'message' => 'success'
            ], 200);
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

    public function trialBalance($year, $month)
    {
        try {
            $tenantFolioLedger = FolioLedger::where('date', 'LIKE', '%' . $year . '-' . $month . '%')->where('company_id', auth('api')->user()->company_id)->where('folio_type', 'Tenant')->with(['tenantFolio' => function ($q) {
                $q->with('tenantContact:id,contact_id,property_id,reference');
            }])->get();
            $ownerFolioLedger = FolioLedger::where('date', 'LIKE', '%' . $year . '-' . $month . '%')->where('company_id', auth('api')->user()->company_id)->where('folio_type', 'Owner')->with(['ownerFolio' => function ($q) {
                $q->with('ownerContacts:id,contact_id,property_id,reference');
            }])->get();
            $supplier = FolioLedger::where('date', 'LIKE', '%' . $year . '-' . $month . '%')->where('company_id', auth('api')->user()->company_id)->where('folio_type', 'Supplier')->with(['supplierDetails' => function ($q) {
                $q->with('supplierContact:id,contact_id,reference');
            }])->get();

            $tenantTotalOpening = $tenantFolioLedger->sum('opening_balance');
            $tenantTotalClosing = $tenantFolioLedger->sum('closing_balance');
            $ownerTotalOpening = $ownerFolioLedger->sum('opening_balance');
            $ownerTotalClosing = $ownerFolioLedger->sum('closing_balance');
            $supplierTotalOpening = $supplier->sum('opening_balance');
            $supplierTotalClosing = $supplier->sum('closing_balance');

            return response()->json([
                'owner' => $ownerFolioLedger,
                'tenant' => $tenantFolioLedger,
                'supplier' => $supplier,
                'tenantOpening' => $tenantTotalOpening,
                'tenantClosing' => $tenantTotalClosing,
                'ownerOpening' => $ownerTotalOpening,
                'ownerClosing' => $ownerTotalClosing,
                'supplierOpening' => $supplierTotalOpening,
                'supplierClosing' => $supplierTotalClosing,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * ------   JOURNAL REPORT FUNCTION    -----------
     * BILL PAYMENT RECEIPT DATA
     * JOURNAL RECEIPT DATA
     */
    public function journalBalance($year, $month)
    {
        try {
            $journalBalance = Receipt::select('id', 'property_id', 'contact_id', 'amount', 'ref', 'receipt_date', 'type', 'payment_method')
                ->where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->whereIn('type', ['Bill', 'Journal'])
                ->where('company_id', auth('api')->user()->company_id)
                ->with('receipt_details');
            $journalBalanceSum = $journalBalance->sum('amount');
            $journalBalance = $journalBalance->get();
            return response()->json([
                'journalBalance' => $journalBalance,
                'journalBalanceSum' => $journalBalanceSum,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function cashBookBalance($year, $month)
    {
        try {
            $cashBookBalance = Receipt::where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->whereIn('type', ['Tenant Receipt', 'Withdraw', 'Invoice', 'Folio Receipt', 'Folio Withdraw'])
                ->where('reverse_status', NULL)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('receipt_details', 'ownerFolio', 'tenantFolio', 'supplierFolio', 'ownerFolio.ownerContacts:id,reference', 'tenantFolio.tenantContact:id,reference', 'supplierFolio.supplierContact:id,reference')
                ->get();

            $cashBookDebitBalance = Receipt::where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->whereIn('type', ['Withdraw', 'Folio Withdraw'])
                ->where('reverse_status', NULL)
                ->where('company_id', auth('api')->user()->company_id)
                ->sum('amount');
            $cashBookCreditBalance = Receipt::where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->whereIn('type', ['Tenant Receipt', 'Invoice', 'Folio Receipt'])
                ->where('reverse_status', NULL)
                ->where('company_id', auth('api')->user()->company_id)
                ->sum('amount');
            $bankDepositList = CurrentAllInOneBankDeposit::where('deposit_date', 'LIKE', '%' . $year . '-' . $month . '%')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'cashBookBalance' => $cashBookBalance,
                'cashBookDebitBalance' => $cashBookDebitBalance,
                'cashBookCreditBalance' => $cashBookCreditBalance,
                'bankDepositList' => $bankDepositList,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function transactionAudit($year, $month)
    {
        try {
            $transactionAudit = Receipt::select('id', 'property_id', 'contact_id', 'amount', 'ref', 'receipt_date', 'folio_type', 'type', 'new_type', 'payment_method', 'owner_folio_id', 'tenant_folio_id', 'supplier_folio_id')
                ->where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->where('company_id', auth('api')->user()->company_id)
                ->with('receipt_details', 'ownerFolio', 'tenantFolio', 'supplierFolio', 'ownerFolio.ownerContacts:id,reference', 'tenantFolio.tenantContact:id,reference', 'supplierFolio.supplierContact:id,reference')
                ->get();
            $debit = Receipt::select('amount')
            ->whereIn('new_type', ['Payment', 'Withdrawal', 'Journal'])
            ->where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
            ->where('company_id', auth('api')->user()->company_id)
            ->sum('amount');
            $credit = Receipt::select('amount')
            ->whereIn('new_type', ['Receipt', 'Payment', 'Journal'])
            ->where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
            ->where('company_id', auth('api')->user()->company_id)
            ->sum('amount');
            return response()->json([
                'transactionAudit' => $transactionAudit,
                'debit' => $debit,
                'credit' => $credit,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
