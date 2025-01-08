<?php

namespace Modules\Accounts\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerDetailsDaily;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\SellerFolio;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\TenantFolio;
use Carbon\Carbon;
class FolioLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index($year, $month)
    {
        try {
            $owner = OwnerFolio::select('id', 'company_id', 'opening_balance', 'property_id', 'owner_contact_id', 'folio_code')->with('ownerContacts:id,property_id,contact_id,reference', 'ownerProperties:id,reference')->with(['folio_ledger' => function ($q) use ($year, $month) {
                $q->where('date', 'LIKE', '%' . $year . '-' . $month . '%');
            }, 'folio_ledger.ledger_details_daily'])->where('company_id', auth('api')->user()->company_id)->get();
            $tenant = TenantFolio::select('id', 'company_id', 'opening_balance', 'property_id', 'tenant_contact_id')->with('tenantContacts:id,reference,contact_id,property_id', 'tenantProperties:id,reference')->with(['folio_ledger' => function ($q) use ($year, $month) {
                $q->where('date', 'LIKE', '%' . $year . '-' . $month . '%');
            }, 'folio_ledger.ledger_details_daily'])->where('company_id', auth('api')->user()->company_id)->get();
            $supplier = SupplierDetails::with('supplierContact:id,contact_id,reference')->with(['folio_ledger' => function ($q) use ($year, $month) {
                $q->where('date', 'LIKE', '%' . $year . '-' . $month . '%');
            }, 'folio_ledger.ledger_details_daily'])->where('company_id', auth('api')->user()->company_id)->get();
            $seller = SellerFolio::with('sellerContacts:id,property_id,contact_id,reference')->with(['folio_ledger' => function ($q) use ($year, $month) {
                $q->where('date', 'LIKE', '%' . $year . '-' . $month . '%');
            }, 'folio_ledger.ledger_details_daily'])->where('company_id', auth('api')->user()->company_id)->get();


            return response()->json([
                'data' => $owner,
                'tenant' => $tenant,
                'supplier' => $supplier,
                'seller' => $seller,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function ownerFolioLedger($id)
    {
        try {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            $owner = OwnerFolio::select('id', 'company_id', 'opening_balance', 'property_id', 'owner_contact_id', 'folio_code')
                ->where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('ownerContacts:id,property_id,contact_id,reference', 'ownerProperties:id,reference')
                ->with(['folio_ledger' => function ($q) use ($currentYear, $currentMonth) {
                    $q->where('date', 'LIKE', '%' . $currentYear . '-' . $currentMonth . '%');
                }, 'folio_ledger.ledger_details_daily'])->first();

            return response()->json([
                'data' => $owner,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function ownerFilteredFolioLedger($id, Request $request)
    {
        try {
            $owner = OwnerFolio::select('id', 'company_id', 'opening_balance', 'property_id', 'owner_contact_id', 'folio_code')
                ->where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('ownerContacts:id,property_id,contact_id,reference', 'ownerProperties:id,reference')
                ->with(['folio_ledger' => function ($q) use ($request) {
                    $q->whereBetween('date', [$request->from_date, $request->to_date]);
                }, 'folio_ledger.ledger_details_daily'])->first();

            return response()->json([
                'data' => $owner,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function supplierFolioLedger($id)
    {
        try {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            $supplier = SupplierDetails::where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('supplierContact')
                ->with(['folio_ledger' => function ($q) use ($currentYear, $currentMonth) {
                    $q->where('date', 'LIKE', '%' . $currentYear . '-' . $currentMonth . '%');
                }, 'folio_ledger.ledger_details_daily'])->first();

            return response()->json([
                'data' => $supplier,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function supplierFilteredFolioLedger($id, Request $request)
    {
        try {
            $supplier = SupplierDetails::where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('supplierContact')
                ->with(['folio_ledger' => function ($q) use ($request) {
                    $q->whereBetween('date', [$request->from_date, $request->to_date]);
                }, 'folio_ledger.ledger_details_daily'])->first();

                // $supplier = SupplierDetails::where('id', $id)->with('supplierContact:id,contact_id,reference')->with(['folio_ledger' => function ($q) use ($request) {
                //     $q->whereBetween('date', 'LIKE', '%' . $request->from_date . '-' . $request->to_date . '%');
                // }, 'folio_ledger.ledger_details_daily'])->where('company_id', auth('api')->user()->company_id)->first();

            return response()->json([
                'data' => $supplier,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function sellerFolioLedger($id)
    {
        try {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            $supplier = SellerFolio::where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('sellerContacts.sellerFolio')
                ->with(['folio_ledger' => function ($q) use ($currentYear, $currentMonth) {
                    $q->where('date', 'LIKE', '%' . $currentYear . '-' . $currentMonth . '%');
                }, 'folio_ledger.ledger_details_daily'])->first();

            return response()->json([
                'data' => $supplier,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function sellerFilteredFolioLedger($id, Request $request)
    {
        try {
            $supplier = SellerFolio::where('id', $id)->where('company_id', auth('api')->user()->company_id)
                ->with('sellerContacts.sellerFolio')
                ->with(['folio_ledger' => function ($q) use ($request) {
                    $q->whereBetween('date', [$request->from_date, $request->to_date]);
                }, 'folio_ledger.ledger_details_daily'])->first();

            return response()->json([
                'data' => $supplier,
                'message' => 'Successful'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function next_date_opening_balance()
    {
        try {
            $folioLedger = FolioLedger::where('updated', 1)->get();
            foreach ($folioLedger as $key => $value) {
                $openingBalance = $value->opening_balance;
                $creditTotal = FolioLedgerDetailsDaily::where('folio_ledgers_id', $value['id'])->where('type', 'credit')->sum('amount');
                // return $creditTotal;
                $debitTotal = FolioLedgerDetailsDaily::where('folio_ledgers_id', $value['id'])->where('type', 'debit')->sum('amount');
                // return $debitTotal;
                $closingBalance = ($openingBalance + $creditTotal) - $debitTotal;
                $value->opening_balance = $closingBalance;
                $value->debit = $debitTotal;
                $value->credit = $creditTotal;
                $value->save();
                // return $value->company_id;
                $nextDate = date('Y-m-d', strtotime($value->date . '+' . 1 . ' days'));
                $folioLedger = new FolioLedger();
                $folioLedger->company_id = $value->company_id;
                $folioLedger->date = $nextDate;
                $folioLedger->folio_id = $value->folio_id;
                $folioLedger->folio_type = $value->folio_type;
                $folioLedger->opening_balance = $closingBalance;
                $folioLedger->closing_balance = 0;
                $folioLedger->updated = 0;
                $folioLedger->save();
                return response()->json([
                    'Status'  => 'Success'
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    public function folioLedgerUpdate()
    {
        $db = DB::transaction(function () {
            $company = Company::all();
            foreach ($company as $value) {
                $supplierDetails = SupplierDetails::where('company_id', $value->id)->get();
                foreach ($supplierDetails as $supplier) {
                    $ledger = FolioLedger::where('folio_type', 'Supplier')->where('folio_id', $supplier->id)->orderBy('id', 'desc')->first();
                    $openingBalance = 0;
                    if (!empty($ledger)) {
                        $openingBalance = $ledger->closing_balance;
                    }
                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = $value->id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $supplier->id;
                    $storeLedger->folio_type = 'Supplier';
                    $storeLedger->opening_balance = $openingBalance;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();
                }

                $ownerFolio = OwnerFolio::where('company_id', $value->id)->get();
                foreach ($ownerFolio as $owner) {
                    $ledger = FolioLedger::where('folio_type', 'Owner')->where('folio_id', $owner->id)->orderBy('id', 'desc')->first();
                    $openingBalance = 0;
                    if (!empty($ledger)) {
                        $openingBalance = $ledger->closing_balance;
                    }
                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = $value->id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $owner->id;
                    $storeLedger->folio_type = 'Owner';
                    $storeLedger->opening_balance = $openingBalance;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();
                }

                $tenantFolio = TenantFolio::where('company_id', $value->id)->get();
                foreach ($tenantFolio as $tenant) {
                    $ledger = FolioLedger::where('folio_type', 'Tenant')->where('folio_id', $tenant->id)->orderBy('id', 'desc')->first();
                    $openingBalance = 0;
                    if (!empty($ledger)) {
                        $openingBalance = $ledger->closing_balance;
                    }
                    $storeLedger = new FolioLedger();
                    $storeLedger->company_id = $value->id;
                    $storeLedger->date = Date('Y-m-d');
                    $storeLedger->folio_id = $tenant->id;
                    $storeLedger->folio_type = 'Tenant';
                    $storeLedger->opening_balance = $openingBalance;
                    $storeLedger->closing_balance = 0;
                    $storeLedger->save();
                }
            }
            return 'Successful';
        });
        return $db;
    }

    public function companyFolioLedgerUpdate()
    {
        DB::transaction(function () {
            $supplierDetails = SupplierDetails::where('company_id', auth('api')->user()->company_id)->get();
            foreach ($supplierDetails as $supplier) {
                $ledger = FolioLedger::where('folio_type', 'Supplier')->where('folio_id', $supplier->id)->orderBy('id', 'desc')->first();
                $openingBalance = 0;
                if (!empty($ledger)) {
                    $openingBalance = $ledger->closing_balance;
                }
                $storeLedger = new FolioLedger();
                $storeLedger->company_id = auth('api')->user()->company_id;
                $storeLedger->date = Date('Y-m-d');
                $storeLedger->folio_id = $supplier->id;
                $storeLedger->folio_type = 'Supplier';
                $storeLedger->opening_balance = $openingBalance;
                $storeLedger->closing_balance = 0;
                $storeLedger->save();
            }

            $ownerFolio = OwnerFolio::where('company_id', auth('api')->user()->company_id)->get();
            foreach ($ownerFolio as $owner) {
                $ledger = FolioLedger::where('folio_type', 'Owner')->where('folio_id', $owner->id)->orderBy('id', 'desc')->first();
                $openingBalance = 0;
                if (!empty($ledger)) {
                    $openingBalance = $ledger->closing_balance;
                }
                $storeLedger = new FolioLedger();
                $storeLedger->company_id = auth('api')->user()->company_id;
                $storeLedger->date = Date('Y-m-d');
                $storeLedger->folio_id = $owner->id;
                $storeLedger->folio_type = 'Owner';
                $storeLedger->opening_balance = $openingBalance;
                $storeLedger->closing_balance = 0;
                $storeLedger->save();
            }

            $tenantFolio = TenantFolio::where('company_id', auth('api')->user()->company_id)->get();
            foreach ($tenantFolio as $tenant) {
                $ledger = FolioLedger::where('folio_type', 'Tenant')->where('folio_id', $tenant->id)->orderBy('id', 'desc')->first();
                $openingBalance = 0;
                if (!empty($ledger)) {
                    $openingBalance = $ledger->closing_balance;
                }
                $storeLedger = new FolioLedger();
                $storeLedger->company_id = auth('api')->user()->company_id;
                $storeLedger->date = Date('Y-m-d');
                $storeLedger->folio_id = $tenant->id;
                $storeLedger->folio_type = 'Tenant';
                $storeLedger->opening_balance = $openingBalance;
                $storeLedger->closing_balance = 0;
                $storeLedger->save();
            }
        });
        return 'Successful';
    }
}
