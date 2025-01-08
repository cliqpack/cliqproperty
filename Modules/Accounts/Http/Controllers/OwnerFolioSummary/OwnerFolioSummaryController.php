<?php

namespace Modules\Accounts\Http\Controllers\OwnerFolioSummary;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Receipt;
use Modules\Accounts\Entities\ReceiptDetails;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Properties\Entities\Properties;
use Modules\Settings\Entities\BrandSettingLogo;
use Modules\Settings\Entities\SettingBrandStatement;

class OwnerFolioSummaryController extends Controller
{
    public function transaction(Request $request, $id)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;

            // Get the relevant receipt IDs based on the given criteria
            $receiptDetails = ReceiptDetails::where(function ($query) use ($id) {
                $query->where('from_folio_id', $id)
                    ->where('from_folio_type', 'Owner')
                    ->orWhere('to_folio_id', $id)
                    ->where('to_folio_type', 'Owner');
            })->pluck('receipt_id');

            // Filter the ReceiptDetails based on property_id from the Receipt table
            $distinctReceiptDetailsQuery = ReceiptDetails::select('account_id')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->groupBy('account_id')
                ->orderBy('account_id');

            if ($request->property_id != 'All') {
                $distinctReceiptDetailsQuery->whereHas('receipt', function ($query) use ($request) {
                    $query->where('property_id', $request->property_id);
                });
            }

            $distinctReceiptDetails = $distinctReceiptDetailsQuery->get();

            $incomeAccounts = $distinctReceiptDetails->filter(function ($detail) {
                return $detail->account && $detail->account->type === 'Income';
            })->values()->all();

            $expenseAccounts = $distinctReceiptDetails->filter(function ($detail) {
                return $detail->account && $detail->account->type === 'Expense';
            })->values()->all();

            // Calculate total amount and tax amount for all account_ids filtered by property_id
            $totalAmountAndTaxQuery = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->selectRaw('SUM(amount) as total_amount_sum')
                ->selectRaw('SUM(taxAmount) as total_tax_sum');

            if ($request->property_id != 'All') {
                $totalAmountAndTaxQuery->whereHas('receipt', function ($query) use ($request) {
                    $query->where('property_id', $request->property_id);
                });
            }

            $totalAmountAndTax = $totalAmountAndTaxQuery->first();
            // Calculate total amount and tax amount for all account_ids filtered by property_id
            $totalDebitAmountAndTaxQuery = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense'); // Filter for Expense accounts
                })
                ->selectRaw('SUM(amount) as total_amount_sum')
                ->selectRaw('SUM(taxAmount) as total_tax_sum');

            if ($request->property_id != 'All') {
                $totalDebitAmountAndTaxQuery->whereHas('receipt', function ($query) use ($request) {
                    $query->where('property_id', $request->property_id);
                });
            }

            $totalDebitAmountAndTax = $totalDebitAmountAndTaxQuery->first();
            // Calculate total amount and tax amount for all account_ids filtered by property_id
            $totalCreditAmountAndTaxQuery = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income'); // Filter for Expense accounts
                })
                ->selectRaw('SUM(amount) as total_amount_sum')
                ->selectRaw('SUM(taxAmount) as total_tax_sum');

            if ($request->property_id != 'All') {
                $totalCreditAmountAndTaxQuery->whereHas('receipt', function ($query) use ($request) {
                    $query->where('property_id', $request->property_id);
                });
            }

            $totalCreditAmountAndTax = $totalCreditAmountAndTaxQuery->first();

            // Get receipts with their relationships, filtering by date range and ordering by ID
            if ($request->property_id == 'All') {
                $receiptsData = Receipt::whereIn('id', $receiptDetails)
                    ->whereBetween('receipt_date', [$from_date, $to_date])
                    ->with('property.ownerOne', 'receipt_details.account')
                    ->orderBy('id', 'DESC')
                    ->get();
            } else {
                $receiptsData = Receipt::where('property_id', $request->property_id)
                    ->whereIn('id', $receiptDetails)
                    ->whereBetween('receipt_date', [$from_date, $to_date])
                    ->with('property.ownerOne', 'receipt_details.account')
                    ->orderBy('id', 'DESC')
                    ->get();
            }

            return response()->json([
                'message' => 'Success',
                'data' => $receiptsData,
                'distinct_receipt_details' => ['data' => $distinctReceiptDetails],
                'income_accounts' => ['data' => $incomeAccounts],
                'expense_accounts' => ['data' => $expenseAccounts],
                'total_amount_sum' => $totalAmountAndTax->total_amount_sum,
                'total_tax_sum' => $totalAmountAndTax->total_tax_sum,
                'total_debit_amount_sum' => $totalDebitAmountAndTax->total_amount_sum,
                'total_debit_tax_sum' => $totalDebitAmountAndTax->total_tax_sum,
                'total_credit_amount_sum' => $totalCreditAmountAndTax->total_amount_sum,
                'total_credit_tax_sum' => $totalCreditAmountAndTax->total_tax_sum,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
    public function odtransaction(Request $request, $id)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;

            // Get the relevant receipt IDs based on the given criteria
            $receiptDetails = ReceiptDetails::where(function ($query) use ($id) {
                $query->where('from_folio_id', $id)
                    ->where('from_folio_type', 'Owner')
                    ->orWhere('to_folio_id', $id)
                    ->where('to_folio_type', 'Owner');
            })->pluck('receipt_id');

            // Filter the ReceiptDetails based on property_id from the Receipt table and date range on created_at
            $distinctBarReceiptDetailsQuery = ReceiptDetails::select(['account_id'])
                ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year")
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereBetween('created_at', [$from_date, $to_date]) // Filter by date range
                ->with('account')
                ->groupBy('month_year')
                ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')");
            $distinctReceiptDetailsQuery = ReceiptDetails::select(['account_id', 'created_at'])
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->with('account')
                ->groupBy('account_id')
                ->orderBy('account_id');

            if ($request->property_id != 'All') {
                $distinctReceiptDetailsQuery->whereHas('receipt', function ($query) use ($request) {
                    $query->where('property_id', $request->property_id);
                });
            }

            $distinctReceiptDetails = $distinctReceiptDetailsQuery->get();

            $incomeAccounts = $distinctReceiptDetails->filter(function ($detail) {
                return $detail->account && $detail->account->type === 'Income';
            })->values()->all();

            $expenseAccounts = $distinctReceiptDetails->filter(function ($detail) {
                return $detail->account && $detail->account->type === 'Expense';
            })->values()->all();

            $distinctBarReceiptDetailsQuery = $distinctBarReceiptDetailsQuery->get();

            $incomeBarAccounts = ReceiptDetails::select(['account_id'])
                ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year")
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereBetween('created_at', [$from_date, $to_date]) // Filter by date range
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income'); // Filter for Expense accounts
                })
                ->groupBy('month_year')
                ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
                ->get();

            $expenseBarAccounts = ReceiptDetails::select(['account_id'])
                ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year")
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereBetween('created_at', [$from_date, $to_date]) // Filter by date range
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense'); // Filter for Expense accounts
                })
                ->groupBy('month_year')
                ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
                ->get();
            $receiptDetails = ReceiptDetails::where('from_folio_id', $id)
                ->where('from_folio_type', 'Owner')
                ->orWhere('to_folio_id', $id)
                ->where('to_folio_type', 'Owner')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->whereNotNull('account_id')
                ->with('account')
                ->get();
            $totaldebit = ReceiptDetails::where('from_folio_id', $id)
                ->where('pay_type', 'debit')
                ->where('from_folio_type', 'Owner')
                ->orWhere('to_folio_id', $id)
                ->where('to_folio_type', 'Owner')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->whereNotNull('account_id')
                ->sum('amount');
            $totalcredit = ReceiptDetails::where('from_folio_id', $id)
                ->where('pay_type', 'credit')
                ->where('from_folio_type', 'Owner')
                ->orWhere('to_folio_id', $id)
                ->where('to_folio_type', 'Owner')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->whereNotNull('account_id')
                ->sum('amount');
            $totaltax = ReceiptDetails::where('from_folio_id', $id)
                ->where('from_folio_type', 'Owner')
                ->orWhere('to_folio_id', $id)
                ->where('to_folio_type', 'Owner')
                ->whereBetween('created_at', [$from_date, $to_date])
                ->whereNotNull('account_id')
                ->sum('taxAmount');

            return response()->json([
                'message' => 'Success',
                'distinct_receipt_details' => ['data' => $distinctBarReceiptDetailsQuery],
                'income_accounts' => ['data' => $incomeAccounts],
                'expense_accounts' => ['data' => $expenseAccounts],
                'incomeBarAccounts' => ['data' => $incomeBarAccounts],
                'expenseBarAccounts' => ['data' => $expenseBarAccounts],
                'totaldebit' => $totaldebit,
                'totalcredit' => $totalcredit,
                'totaltax' => $totaltax,
                'receiptDetails' => $receiptDetails,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function properties($folio_id)
    {
        try {
            $properties = Properties::select(['id', 'reference'])->where('owner_folio_id', $folio_id)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $properties, 'message' => 'Successful']);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }

    public function summaryByReport(Request $request, $id)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $brandSettingStatement = SettingBrandStatement::select(['header_height_by_millimeter', 'logo_maximum_height', 'logo_position', 'logo_width', 'primary_colour', 'secondary_colour', 'third_colour'])->where('company_id', auth('api')->user()->company_id)->first();
            $brandSettingLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
            $ownerfolio = OwnerFolio::select(['id', 'owner_contact_id', 'folio_code', 'property_id'])->where('id', $id)->where('status', true)->with(['ownerContacts:id,reference,contact_id', 'ownerContacts.owner_address', 'ownerProperties:id', 'ownerProperties.property_address'])->first();
            // Get the relevant receipt IDs based on the given criteria
            $receiptDetails = ReceiptDetails::where(function ($query) use ($id, $from_date, $to_date) {
                $query->where('from_folio_id', $id)
                    ->where('from_folio_type', 'Owner')
                    ->orWhere('to_folio_id', $id)
                    ->where('to_folio_type', 'Owner')
                    ->whereBetween('created_at', [$from_date, $to_date]);
            })->pluck('receipt_id');

            // Filter the ReceiptDetails based on property_id from the Receipt table
            $distinctReceiptDetailsQuery = ReceiptDetails::select('account_id')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNull('supplier_folio_id')
                ->groupBy('account_id')
                ->orderBy('account_id')
                ->get();
            // Filter the ReceiptDetails based on property_id from the Receipt table
            $accountTransaction = ReceiptDetails::select('account_id')
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNotNull('supplier_folio_id')
                ->whereHas('supplierFolio', function ($query) {
                    $query->where('system_folio', true); // Filter for Expense accounts
                })
                ->groupBy('account_id')
                ->orderBy('account_id')
                ->get();

            $totalaccountTransactionAmount = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNotNull('supplier_folio_id')
                ->whereHas('supplierFolio', function ($query) {
                    $query->where('system_folio', true); // Filter for Expense accounts
                })->sum('amount');
            $totalTaxAmount = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->sum('taxAmount');
            $totalMoneyin = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income'); // Filter for Expense accounts
                })
                ->sum('amount');
            $totalMoneyOut = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense'); // Filter for Expense accounts
                })
                ->sum('amount');
            $subtotalMoneyin = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereNull('supplier_folio_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income'); // Filter for Expense accounts
                })
                ->sum('amount');
            $subtotalMoneyOut = ReceiptDetails::select('account_id')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereNull('supplier_folio_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense'); // Filter for Expense accounts
                })
                ->sum('amount');
            $totalBalance = 0;
            if (($totalMoneyin - $totalMoneyOut) > 0) {
                $totalBalance = $totalMoneyin - $totalMoneyOut;
            }

            return response()->json([
                'message' => 'Success',
                'data' => $distinctReceiptDetailsQuery,
                'accountTransaction' => $accountTransaction,
                'totalaccountTransactionAmount' => $totalaccountTransactionAmount,
                'totalTaxAmount' => $totalTaxAmount,
                'totalMoneyin' => $totalMoneyin,
                'totalMoneyOut' => $totalMoneyOut,
                'subtotalMoneyin' => $subtotalMoneyin,
                'subtotalMoneyOut' => $subtotalMoneyOut,
                'totalBalance' => $totalBalance,
                'ownerfolio' => $ownerfolio,
                'brandSettingStatement' => $brandSettingStatement,
                'brandSettingLogo' => $brandSettingLogo,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }

    public function summaryByMonthInfo(Request $request, $id)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $brandSettingStatement = SettingBrandStatement::select(['header_height_by_millimeter', 'logo_maximum_height', 'logo_position', 'logo_width', 'primary_colour', 'secondary_colour', 'third_colour'])->where('company_id', auth('api')->user()->company_id)->first();
            $brandSettingLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
            $ownerfolio = OwnerFolio::select(['id', 'owner_contact_id', 'folio_code', 'property_id'])
                ->where('id', $id)
                ->where('status', true)
                ->with(['ownerContacts:id,reference,contact_id', 'ownerContacts.owner_address', 'ownerProperties:id', 'ownerProperties.property_address'])
                ->first();

            // Get the relevant receipt IDs based on the given criteria
            $receiptDetails = ReceiptDetails::where(function ($query) use ($id, $from_date, $to_date) {
                $query->where('from_folio_id', $id)
                    ->where('from_folio_type', 'Owner')
                    ->orWhere('to_folio_id', $id)
                    ->where('to_folio_type', 'Owner')
                    ->whereBetween('created_at', [$from_date, $to_date]);
            })->pluck('receipt_id');

            // Query to get data grouped by account and month
            $distinctReceiptDetailsQuery = ReceiptDetails::select('account_id')
                ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year") // Group by month and year
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNull('supplier_folio_id')
                ->groupBy('account_id', 'month_year') // Group by account_id and month_year
                ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
                ->get();

            // Query to get account transactions, grouped by account and month
            $accountTransaction = ReceiptDetails::select('account_id')
                ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year") // Group by month and year
                ->selectRaw('SUM(amount) as total_amount')
                ->selectRaw('SUM(taxAmount) as total_tax_amount')
                ->whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNotNull('supplier_folio_id')
                ->whereHas('supplierFolio', function ($query) {
                    $query->where('system_folio', true); // Filter for Expense accounts
                })
                ->groupBy('account_id', 'month_year') // Group by account_id and month_year
                ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
                ->get();

            // Calculating the total values as you did previously
            $totalaccountTransactionAmount = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereNotNull('supplier_folio_id')
                ->whereHas('supplierFolio', function ($query) {
                    $query->where('system_folio', true);
                })
                ->sum('amount');

            $totalTaxAmount = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->sum('taxAmount');

            $totalMoneyin = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income');
                })
                ->sum('amount');

            $totalMoneyOut = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense');
                })
                ->sum('amount');

            $subtotalMoneyin = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereNull('supplier_folio_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Income');
                })
                ->sum('amount');

            $subtotalMoneyOut = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
                ->whereNotNull('account_id')
                ->whereNull('supplier_folio_id')
                ->with('account')
                ->whereHas('account', function ($query) {
                    $query->where('type', 'Expense');
                })
                ->sum('amount');

            $totalBalance = ($totalMoneyin - $totalMoneyOut) > 0 ? $totalMoneyin - $totalMoneyOut : 0;


            // Initialize an empty array to hold the transformed data
            $groupedData = [];

            // Loop through each item in the data array
            foreach ($distinctReceiptDetailsQuery as $item) {
                $month_year = $item['month_year'];

                // If the month_year is not already a key in the grouped data array, add it
                if (!isset($groupedData[$month_year])) {
                    $groupedData[$month_year] = [
                        'month_year' => $month_year,
                        'data' => []
                    ];
                }

                // Add the current item to the appropriate month_year group
                $groupedData[$month_year]['data'][] = $item;
            }

            // Convert the associative array to an indexed array
            $groupedData = array_values($groupedData);
            // Initialize an empty array to hold the transformed data
            $groupedAcTransactionData = [];

            // Loop through each item in the data array
            foreach ($accountTransaction as $item) {
                $month_year = $item['month_year'];

                // If the month_year is not already a key in the grouped data array, add it
                if (!isset($groupedAcTransactionData[$month_year])) {
                    $groupedAcTransactionData[$month_year] = [
                        'month_year' => $month_year,
                        'data' => []
                    ];
                }

                // Add the current item to the appropriate month_year group
                $groupedAcTransactionData[$month_year]['data'][] = $item;
            }

            // Convert the associative array to an indexed array
            $groupedAcTransactionData = array_values($groupedAcTransactionData);



            return response()->json([
                'message' => 'Success',
                'data' => $groupedData,
                'accountTransaction' => $groupedAcTransactionData,
                'totalaccountTransactionAmount' => $totalaccountTransactionAmount,
                'totalTaxAmount' => $totalTaxAmount,
                'totalMoneyin' => $totalMoneyin,
                'totalMoneyOut' => $totalMoneyOut,
                'subtotalMoneyin' => $subtotalMoneyin,
                'subtotalMoneyOut' => $subtotalMoneyOut,
                'totalBalance' => $totalBalance,
                'ownerfolio' => $ownerfolio,
                'brandSettingStatement' => $brandSettingStatement,
                'brandSettingLogo' => $brandSettingLogo,
            ]);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
