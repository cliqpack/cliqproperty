<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Disbursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Modules\Accounts\Entities\GeneratedWithdrawal;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\Withdrawal;
use Modules\Settings\Entities\BankingSetting;
use stdClass;

class WithdrawController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
    }

    public function cutString($name, $totalLen)
    {
        $nameLen = strlen($name);
        if ($nameLen > $totalLen) {
            $name = substr($name, 0, $totalLen - 1);
        } else {
            $remaininglen = $totalLen - $nameLen;
            for ($i = 0; $i < $remaininglen; $i++) {
                $name .= " ";
            }
        }
        return ['name' => $name, 'nameLen' => $nameLen];
    }
    public function stringInsert($str, $insertstr, $pos)
    {
        $str = substr($str, 0, $pos) . $insertstr . substr($str, $pos);
        return $str;
    }

    public function withdrawals()
    {
        try {
            $withdrawalList = Withdrawal::where('create_date', 'LIKE', '%' . date('Y-m') . '%')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $withdrawalList, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function allWithdrawal()
    {
        try {
            $totalWithdrawal = Withdrawal::select('*')->where('status', false)->where('company_id', auth('api')->user()->company_id)->count();
            return response()->json(['data' => $totalWithdrawal, 'message' => 'Successful'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function withdrawList($month, $year, $type)
    {
        try {
            if ($type === 'EFT') {
                $withdrawalList = Withdrawal::where('payment_type', 'EFT')->where('status', false)->where('company_id', auth('api')->user()->company_id)->get();
                return response()->json(['data' => $withdrawalList, 'message' => 'Successful'], 200);
            } elseif ($type === 'CHEQUE') {
                $withdrawalList = Withdrawal::where('payment_type', 'Cheque')->where('status', false)->where('company_id', auth('api')->user()->company_id)->get();
                return response()->json(['data' => $withdrawalList, 'message' => 'Successful'], 200);
            } elseif ($type === 'BPAY') {
                $withdrawalList = Withdrawal::where('payment_type', 'BPay')->where('status', false)->where('company_id', auth('api')->user()->company_id)->with('property')->get();
                return response()->json(['data' => $withdrawalList, 'message' => 'Successful'], 200);
            } elseif ($type === 'GENERATED_BATCH') {
                $GeneratedWithdrawalList = GeneratedWithdrawal::where('company_id', auth('api')->user()->company_id)->whereIn('payment_type', ['EFT', 'BPay'])->get();
                return response()->json(['data' => $GeneratedWithdrawalList, 'message' => 'Successful'], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function AllWithdrawList($month, $year)
    {
        try {
            $withdrawalList = Receipt::where('receipt_date', 'LIKE', '%' . $year . '-' . $month . '%')
                ->whereIn('type', ['Folio Withdraw', 'Withdraw'])
                ->where('reversed', false)
                ->where('company_id', auth('api')->user()->company_id)
                ->with('ownerFolio', 'tenantFolio', 'supplierFolio', 'ownerFolio.ownerContacts:id,reference', 'tenantFolio.tenantContact:id,reference', 'supplierFolio.supplierContact:id,reference')
                ->get();
            return response()->json(['data' => $withdrawalList, 'message' => 'Successfull'], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function AllWithdrawReportList($month, $year)
    {
        try {
            $receipt = Receipt::where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->whereIn('type', ['Withdraw', 'Folio Withdraw'])->where('company_id', auth('api')->user()->company_id)->with('property:id,reference', 'contact:id,reference', 'receipt_details')->get();
            $totalAmount = Receipt::select('id', 'amount')->where('receipt_date', 'Like', '%' . $year . '-' . $month . '%')->whereIn('type', ['Withdraw', 'Folio Withdraw'])->where('company_id', auth('api')->user()->company_id)->sum('amount');
            return response()->json(['data' => $receipt, 'totalAmount' => $totalAmount, 'message' => 'Successfull'], 200);
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
        DB::transaction(function () use ($request) {
            foreach ($request->disburse as $value) {
                Disbursement::where('id', $value['id'])->where('company_id', $value['company_id'])->update([
                    'disbursed' => 1
                ]);
            }
        });
        return response()->json([
            'message' => 'Withdrawn',
            'Status' => 'Success'
        ], 200);
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

    public function eftBpayWithdraw(Request $request)
    {
        try {
            $bankDetails = BankingSetting::where('company_id', auth('api')->user()->company_id)->with('company', 'bank', 'fileFormat')->first();
            if (empty($bankDetails->bank)) {
                return response()->json(['status' => 'BANK_DATA_NOT_FOUND', 'message' => 'Please select a bank in banking settings']);
            }
            $batchId = NULL;
            DB::transaction(function () use ($request, &$batchId) {
                $totalAmount = 0;
                $withdrawArray = [];
                $totalWithdrawal = count($request->data);
                foreach ($request->data as $value) {
                    $totalAmount += $value['amount'];
                    if ($value['contact_type'] === "Owner") {
                        Withdrawal::where('id', $value['id'])->update(['status' => true]);

                        $pushObject = new stdClass();
                        $pushObject->payee = $value['owner_payment']['payee'];
                        $pushObject->bsb = $value['owner_payment']['bsb'];
                        $pushObject->account = $value['owner_payment']['account'];
                        $pushObject->amount = $value['amount'];
                        array_push($withdrawArray, $pushObject);
                    } elseif ($value['contact_type'] === "Supplier") {
                        Withdrawal::where('id', $value['id'])->update(['status' => true]);

                        $pushObject = new stdClass();
                        $pushObject->payee = $value['supplier_payment']['payee'];
                        $pushObject->bsb = $value['supplier_payment']['bsb'];
                        $pushObject->account = $value['supplier_payment']['account_no'];
                        $pushObject->amount = $value['amount'];
                        array_push($withdrawArray, $pushObject);
                    } elseif ($value['contact_type'] === "Tenant") {
                        Withdrawal::where('id', $value['id'])->update(['status' => true]);

                        $pushObject = new stdClass();
                        $pushObject->payee = $value['tenant_payment']['payee'];
                        $pushObject->bsb = $value['tenant_payment']['bsb'];
                        $pushObject->account = $value['tenant_payment']['account'];
                        $pushObject->amount = $value['amount'];
                        array_push($withdrawArray, $pushObject);
                    }
                }
                $genWithdraw = GeneratedWithdrawal::latest()->first();
                $generatedBatch = new GeneratedWithdrawal();
                $generatedBatch->create_date = date('Y-m-d');
                if (!empty($genWithdraw)) {
                    $generatedBatch->batch = $genWithdraw->batch + 1;
                } else
                    $generatedBatch->batch = 1;
                $generatedBatch->payment_type = $request->type;
                $generatedBatch->amount = $totalAmount;
                $generatedBatch->total_withdrawals = $totalWithdrawal;
                $generatedBatch->statement = 'Include ' . $totalWithdrawal . ' ' . $request->type . ' withdrawals';
                $generatedBatch->company_id = auth('api')->user()->company_id;
                $generatedBatch->save();
                $batchId = $generatedBatch->id;
                $data = [
                    'id' => $generatedBatch->id,
                    'date' => date('d/m/Y'),
                    'withdrawArray' => $withdrawArray,
                    'type' => 'EFT',
                ];
                $triggerDocument = new DocumentGenerateController();
                $triggerDocument->generateBatchDocument($data);
            });

            $myfile = fopen("public/Document/ABA" . rand() . ".aba", "w") or die("Unable to open file!");
            $bnDetails = $this->cutString($bankDetails->account_name, 26);
            $bankName = $bnDetails['name'];
            $banking_bsb = substr_replace((string)$bankDetails->bsb, '-', 3, 0);
            $de_user_id = $bankDetails->de_user_id;
            $fullTXT = '';
            $txt = "0" . "                 " . "01" . $bankDetails->bank->short_name . "       " . $bankName . $de_user_id . "Disburse    " . date('dmy') . "                                        \n";
            $fullTXT .= $txt;
            $totalamount = 0;
            foreach ($request->data as $value) {
                $totalamount += $value['amount'];
                $amountLen = strlen($value['amount']);
                $amount = $value['amount'];
                $name = '';
                $client_bsb = '';
                $client_acc = '';
                $client_acc_len = '';
                if ($value['contact_type'] === "Owner") {
                    $nameDetails = $this->cutString($value['owner_payment']['owner_contacts']['reference'], 32);
                    $name = $nameDetails['name'];
                    $client_bsb = $value['owner_payment']['bsb'];
                    $client_acc = $value['owner_payment']['account'];
                    $client_acc_len = strlen($value['owner_payment']['account']);
                } elseif ($value['contact_type'] === "Supplier") {
                    $nameDetails = $this->cutString($value['supplier_payment']['supplier_contact']['reference'], 32);
                    $name = $nameDetails['name'];
                    $client_bsb = $value['supplier_payment']['bsb'];
                    $client_acc = $value['supplier_payment']['account_no'];
                    $client_acc_len = strlen($value['supplier_payment']['account_no']);
                } elseif ($value['contact_type'] === "Tenant") {
                    $nameDetails = $this->cutString($value['tenant_payment']['tenant_contacts']['reference'], 32);
                    $name = $nameDetails['name'];
                    $client_bsb = $value['tenant_payment']['bsb'];
                    $client_acc = $value['tenant_payment']['account'];
                    $client_acc_len = strlen($value['tenant_payment']['account']);
                }
                $client_bsb = $this->stringInsert($client_bsb, '-', 3);
                if ($client_acc_len > 10) {
                    $client_acc = substr($client_acc, 0, 9);
                } else {
                    $remaininglen = 10 - $client_acc_len;
                    for ($i = 0; $i < $remaininglen; $i++) {
                        $client_acc = "0" . $client_acc;
                    }
                }
    
                if ($amountLen > 10) {
                    $amount = substr($amount, 0, 9);
                } else {
                    $remaininglen = 10 - $amountLen;
                    for ($i = 0; $i < $remaininglen; $i++) {
                        $amount = "0" . $amount;
                    }
                }
                $fullTXT .= "1" . $client_bsb . $client_acc . "N" . "50" . $amount . $name . $bankDetails->default_statement_description."   " . $banking_bsb . $bankDetails->account_number . $bankName."    " . "00000000\n";
            }
            $totalamountLen = strlen(strval($totalamount));
            if ($totalamountLen > 10) {
                $totalamount = substr(strval($totalamount), 0, 9);
            } else {
                $remaininglen = 10 - $totalamountLen;
                for ($i = 0; $i < $remaininglen; $i++) {
                    $totalamount = "0" . $totalamount;
                }
            }
            $txt = "1" . $banking_bsb . $bankDetails->account_number . "N" . "50" . $totalamount . $bankName."                    " . "Batch ".$batchId."         " . $banking_bsb . $bankDetails->account_number . $bankName."    " . "00000000\n";
            $fullTXT .= $txt;
            $txt = "7" . "999-999            " . "0000000000" . $totalamount . $totalamount . "                        " . "000002" . "                                        \n";
            $fullTXT .= $txt;
            fwrite($myfile, $fullTXT);

            return response()->json([
                'data' => $fullTXT,
                'message' => 'EFT Withdrawn',
                'status' => 'Success'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function chequeWithdraw(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $value) {
                    Withdrawal::where('id', $value['id'])->update(['status' => true]);

                    $generatedBatch = new GeneratedWithdrawal();
                    $generatedBatch->create_date = date('Y-m-d');
                    $generatedBatch->batch = 1;
                    $generatedBatch->payment_type = 'Cheque';
                    $generatedBatch->amount = $value['amount'];
                    $generatedBatch->total_withdrawals = 1;
                    if ($value['contact_type'] === "Owner") {
                        $generatedBatch->statement = 'Withdrawal to ' . $value['owner_payment']['payee'] . ' by Cheque';
                    } elseif ($value['contact_type'] === "Tenant") {
                        $generatedBatch->statement = 'Withdrawal to ' . $value['tenant_payment']['payee'] . ' by Cheque';
                    } elseif ($value['contact_type'] === "Supplier") {
                        $generatedBatch->statement = 'Withdrawal to ' . $value['supplier_payment']['payee'] . ' by Cheque';
                    }

                    $generatedBatch->company_id = auth('api')->user()->company_id;
                    $generatedBatch->save();
                }
            });

            return response()->json([
                'message' => 'Withdrawn',
                'Status' => 'Success'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function onlyBpayWithdraw(Request $request)
    {
        $bank_name = '';
        $fullTXT = [];
        $bankDetails = BankingSetting::where('company_id', auth('api')->user()->company_id)->with('company', 'bank', 'fileFormat')->first();
        if (empty($bankDetails->bank)) {
            return response()->json(['status' => 'BANK_DATA_NOT_FOUND', 'message' => 'Please select a bank in banking settings']);
        }
        if (empty($bankDetails->bpay_enable)||$bankDetails->bpay_enable=='0') {
            return response()->json(['status' => 'Please_enable_BPay', 'message' => 'Please enable BPay']);
        }
        if ($bankDetails->bank_id == '1') {
            $myfile = fopen("public/Document/WBC_" . $bankDetails->company->company_name . rand() . ".csv", "w") or die("Unable to open file!");
            $bnDetails = $this->cutString($bankDetails->bank->bank_name, 26);
            $bankName = $bnDetails['name'];
            $de_user_id = $bankDetails->bank->de_user_id;
            $bank_name = 'wbc';
            $totalamount = 0;
            foreach ($request->data as $value) {
                $totalamount += $value['amount'];
                $amount = $value['amount'];
                $name = '';
                $ref_id = '';
                $client_acc = '';
                if ($value['contact_type'] === "Supplier") {
                    $nameDetails = $this->cutString($value['supplier_payment']['supplier_contact']['reference'], 32);
                    $ref_id = $value['supplier_payment']['supplier_contact']['id'];
                    $name = $nameDetails['name'];
                    // $client_bsb = $value['supplier_payment']['bsb'];
                    $client_acc = $value['supplier_payment']['biller_code'];
                    // $client_acc_len = strlen($value['supplier_payment']['account_no']);
                }

                array_push($fullTXT, [$client_acc, $ref_id, $amount, $name]);
            }
            foreach ($fullTXT as $row) {
                fputcsv($myfile, $row);
            }
            fclose($myfile);
        } else if ($bankDetails->bank_id == '5') {
            $myfile = fopen("public/Document/NAB_" . $bankDetails->company->company_name . rand() . ".txt", "w") or die("Unable to open file!");
            $bnDetails = $this->cutString($bankDetails->bank->bank_name, 26);
            $bankName = $bnDetails['name'];
            $de_user_id = $bankDetails->bank->de_user_id;

            $bank_name = 'nba';
            $totalamount = 0;
            $i=2;
            $fullTXT = "FH1 " . $de_user_id . " " . $bankDetails->customer_name .  "    " . date('Ymd') . "                                        \n";

            foreach ($request->data as $value) {
                $totalamount += $value['amount'];
                $amount = $value['amount'];
                $name = '';
                $ref_id = '';
                $client_acc = '';
                if ($value['contact_type'] === "Supplier") {
                    $nameDetails = $this->cutString($value['supplier_payment']['supplier_contact']['reference'], 32);
                    $ref_id = $value['supplier_payment']['supplier_contact']['id'];
                    $name = $nameDetails['name'];
                    // $client_bsb = $value['supplier_payment']['bsb'];
                    $client_acc = $value['supplier_payment']['biller_code'];
                    // $client_acc_len = strlen($value['supplier_payment']['account_no']);
                }

                $fullTXT .= "FD".$i." 00" . $client_acc ." ". $bankDetails->bsb . " ". $bankDetails->account_number . " ref ". $amount ." ". $name . "\n";
                $i++;
            }
            $fullTXT .= "FT".$i."  00" . ($i-2). " 00" . $totalamount . "\n";
            fclose($myfile);
        }

        try {
            DB::transaction(function () use ($request) {
                $totalAmount = 0;
                $withdrawArray = [];
                $totalWithdrawal = count($request->data);
                foreach ($request->data as $value) {
                    $totalAmount += $value['amount'];
                    if ($value['contact_type'] === "Supplier") {
                        Withdrawal::where('id', $value['id'])->update(['status' => true]);

                        $pushObject = new stdClass();
                        $pushObject->payee = $value['supplier_payment']['payee'];
                        $pushObject->bsb = $value['supplier_payment']['bsb'];
                        $pushObject->account = $value['supplier_payment']['account_no'];
                        $pushObject->amount = $value['amount'];
                        array_push($withdrawArray, $pushObject);
                    }
                }
                $genWithdraw = GeneratedWithdrawal::latest()->first();
                $generatedBatch = new GeneratedWithdrawal();
                $generatedBatch->create_date = date('Y-m-d');
                if (!empty($genWithdraw)) {
                    $generatedBatch->batch = $genWithdraw->batch + 1;
                } else
                    $generatedBatch->batch = 1;
                $generatedBatch->payment_type = $request->type;
                $generatedBatch->amount = $totalAmount;
                $generatedBatch->total_withdrawals = $totalWithdrawal;
                $generatedBatch->statement = 'Include ' . $totalWithdrawal . ' ' . $request->type . ' withdrawals';
                $generatedBatch->company_id = auth('api')->user()->company_id;
                $generatedBatch->save();

                $data = [
                    'id' => $generatedBatch->id,
                    'date' => date('d/m/Y'),
                    'withdrawArray' => $withdrawArray,
                    'type' => 'BPay',
                ];
                $triggerDocument = new DocumentGenerateController();
                $triggerDocument->generateBatchDocument($data);
            });

            return response()->json([
                'data' => $fullTXT,
                'bank' => $bank_name,
                'message' => 'BPay Withdrawn',
                'status' => 'Success'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
