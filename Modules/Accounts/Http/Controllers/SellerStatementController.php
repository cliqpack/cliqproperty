<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SellerStatementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        // try {
        //     $from_date = $request->from_date;
        //     $to_date = $request->to_date;
        //     $brandSettingStatement = SettingBrandStatement::select(['header_height_by_millimeter', 'logo_maximum_height', 'logo_position', 'logo_width', 'primary_colour', 'secondary_colour', 'third_colour'])->where('company_id', auth('api')->user()->company_id)->first();
        //     $brandSettingLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        //     $ownerfolio = OwnerFolio::select(['id', 'owner_contact_id', 'folio_code', 'property_id'])
        //         ->where('id', $id)
        //         ->where('status', true)
        //         ->with(['ownerContacts:id,reference,contact_id', 'ownerContacts.owner_address', 'ownerProperties:id', 'ownerProperties.property_address'])
        //         ->first();

        //     // Get the relevant receipt IDs based on the given criteria
        //     $receiptDetails = ReceiptDetails::where(function ($query) use ($id, $from_date, $to_date) {
        //         $query->where('from_folio_id', $id)
        //             ->where('from_folio_type', 'Owner')
        //             ->orWhere('to_folio_id', $id)
        //             ->where('to_folio_type', 'Owner')
        //             ->whereBetween('created_at', [$from_date, $to_date]);
        //     })->pluck('receipt_id');

        //     // Query to get data grouped by account and month
        //     $distinctReceiptDetailsQuery = ReceiptDetails::select('account_id')
        //         ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year") // Group by month and year
        //         ->selectRaw('SUM(amount) as total_amount')
        //         ->selectRaw('SUM(taxAmount) as total_tax_amount')
        //         ->whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->whereNull('supplier_folio_id')
        //         ->groupBy('account_id', 'month_year') // Group by account_id and month_year
        //         ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
        //         ->get();

        //     // Query to get account transactions, grouped by account and month
        //     $accountTransaction = ReceiptDetails::select('account_id')
        //         ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_year") // Group by month and year
        //         ->selectRaw('SUM(amount) as total_amount')
        //         ->selectRaw('SUM(taxAmount) as total_tax_amount')
        //         ->whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->whereNotNull('supplier_folio_id')
        //         ->whereHas('supplierFolio', function ($query) {
        //             $query->where('system_folio', true); // Filter for Expense accounts
        //         })
        //         ->groupBy('account_id', 'month_year') // Group by account_id and month_year
        //         ->orderByRaw("STR_TO_DATE(month_year, '%b %Y')")
        //         ->get();

        //     // Calculating the total values as you did previously
        //     $totalaccountTransactionAmount = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->whereNotNull('supplier_folio_id')
        //         ->whereHas('supplierFolio', function ($query) {
        //             $query->where('system_folio', true);
        //         })
        //         ->sum('amount');

        //     $totalTaxAmount = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->sum('taxAmount');

        //     $totalMoneyin = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->whereHas('account', function ($query) {
        //             $query->where('type', 'Income');
        //         })
        //         ->sum('amount');

        //     $totalMoneyOut = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->with('account')
        //         ->whereHas('account', function ($query) {
        //             $query->where('type', 'Expense');
        //         })
        //         ->sum('amount');

        //     $subtotalMoneyin = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->whereNull('supplier_folio_id')
        //         ->with('account')
        //         ->whereHas('account', function ($query) {
        //             $query->where('type', 'Income');
        //         })
        //         ->sum('amount');

        //     $subtotalMoneyOut = ReceiptDetails::whereIn('receipt_id', $receiptDetails)
        //         ->whereNotNull('account_id')
        //         ->whereNull('supplier_folio_id')
        //         ->with('account')
        //         ->whereHas('account', function ($query) {
        //             $query->where('type', 'Expense');
        //         })
        //         ->sum('amount');

        //     $totalBalance = ($totalMoneyin - $totalMoneyOut) > 0 ? $totalMoneyin - $totalMoneyOut : 0;


        //     // Initialize an empty array to hold the transformed data
        //     $groupedData = [];

        //     // Loop through each item in the data array
        //     foreach ($distinctReceiptDetailsQuery as $item) {
        //         $month_year = $item['month_year'];

        //         // If the month_year is not already a key in the grouped data array, add it
        //         if (!isset($groupedData[$month_year])) {
        //             $groupedData[$month_year] = [
        //                 'month_year' => $month_year,
        //                 'data' => []
        //             ];
        //         }

        //         // Add the current item to the appropriate month_year group
        //         $groupedData[$month_year]['data'][] = $item;
        //     }

        //     // Convert the associative array to an indexed array
        //     $groupedData = array_values($groupedData);
        //     // Initialize an empty array to hold the transformed data
        //     $groupedAcTransactionData = [];

        //     // Loop through each item in the data array
        //     foreach ($accountTransaction as $item) {
        //         $month_year = $item['month_year'];

        //         // If the month_year is not already a key in the grouped data array, add it
        //         if (!isset($groupedAcTransactionData[$month_year])) {
        //             $groupedAcTransactionData[$month_year] = [
        //                 'month_year' => $month_year,
        //                 'data' => []
        //             ];
        //         }

        //         // Add the current item to the appropriate month_year group
        //         $groupedAcTransactionData[$month_year]['data'][] = $item;
        //     }

        //     // Convert the associative array to an indexed array
        //     $groupedAcTransactionData = array_values($groupedAcTransactionData);



        //     return response()->json([
        //         'message' => 'Success',
        //         'data' => $groupedData,
        //         'accountTransaction' => $groupedAcTransactionData,
        //         'totalaccountTransactionAmount' => $totalaccountTransactionAmount,
        //         'totalTaxAmount' => $totalTaxAmount,
        //         'totalMoneyin' => $totalMoneyin,
        //         'totalMoneyOut' => $totalMoneyOut,
        //         'subtotalMoneyin' => $subtotalMoneyin,
        //         'subtotalMoneyOut' => $subtotalMoneyOut,
        //         'totalBalance' => $totalBalance,
        //         'ownerfolio' => $ownerfolio,
        //         'brandSettingStatement' => $brandSettingStatement,
        //         'brandSettingLogo' => $brandSettingLogo,
        //     ]);
        // } catch (\Exception $ex) {
        //     return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        // }
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
