<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\RecurringInvoice;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\TenantContact;
use Modules\Contacts\Entities\TenantFolio;

class RentManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('contacts::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('contacts::create');
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
        return view('contacts::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('contacts::edit');
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

    public function custom_index($id, $prop_Id)
    {
        try {
            $data = RentManagement::where('tenant_id', $id)->where('property_id', $prop_Id)->with('rentAdjustment', 'rentReceipt', 'rentDiscount')->get();
            $tenant = TenantContact::where('id', $id)->where('property_id', $prop_Id)->where('status', 'true')->first();
            // $tenantFolio = $tenant->tenantFolio;
            return response()->json([
                'data' => $data,
                'tenant' => $tenant,
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
    public function custom_index_ssr(Request $request, $id, $prop_Id)
    {
        try {
            $page_qty = $request->sizePerPage;
            $offset = 0;
            $offset = $page_qty * ($request->page - 1);

            $recurring_invoice = TenantFolio::where('company_id', auth('api')->user()->company_id)->where('property_id', $prop_Id)->where('tenant_contact_id', $id)->pluck('rent_invoice');
            $recurring_invoice = $recurring_invoice[0];
            $rent_invoice = Invoices::where('tenant_contact_id', $id)->where('property_id', $prop_Id)->where('company_id', auth('api')->user()->company_id)->where('rent_management_id', '!=', NULL)->where('status', 'Unpaid')->count();
            $tenant = TenantContact::where('id', $id)->where('property_id', $prop_Id)->where('status', 'true')->first();
            if ($request->q != 'null') {
                $rent_adjustment = DB::table('rent_management')->join('rent_details', 'rent_management.rent_adjustment_id', '=', 'rent_details.id')->groupBy('rent_management.id')->where('rent_management.company_id', auth('api')->user()->company_id)->where('rent_details.rent_amount', 'like', '%' . $request->q . '%')->orWhere('rent_details.active_date', 'like', '%' . $request->q . '%')->pluck('rent_management.id');
                $rent_discount = DB::table('rent_management')->join('rent_discounts', 'rent_management.rent_discount_id', '=', 'rent_discounts.id')->groupBy('rent_management.id')->where('rent_management.company_id', auth('api')->user()->company_id)->where('rent_discounts.discount_amount', 'like', '%' . $request->q . '%')->pluck('rent_management.id');
                $data = RentManagement::where('tenant_id', $id)
                    ->where('property_id', $prop_Id)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('from_date', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to_date', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rent', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('credit', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('received', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('rent_adjustment_id', $rent_adjustment)
                    ->orWhereIn('rent_discount_id', $rent_discount)
                    ->with('rentAdjustment', 'rentReceipt', 'rentDiscount')
                    ->offset($offset)->limit($page_qty)
                    ->orderBy($request->sortField, $request->sortValue)
                    ->get();
                $all = RentManagement::where('tenant_id', $id)
                    ->where('company_id', auth('api')->user()->company_id)
                    ->where('property_id', $prop_Id)
                    ->where('from_date', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('to_date', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rent', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('due', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('credit', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('received', 'LIKE', '%' . $request->q . '%')
                    ->orWhereIn('rent_adjustment_id', $rent_adjustment)
                    ->orWhereIn('rent_discount_id', $rent_discount)
                    ->count();
            } else {
                $data = RentManagement::where('tenant_id', $id)->where('property_id', $prop_Id)->where('company_id', auth('api')->user()->company_id)->with('rentAdjustment', 'rentReceipt', 'rentDiscount')->offset($offset)->limit($page_qty)->orderBy($request->sortField, $request->sortValue)->get();
                $all = RentManagement::where('tenant_id', $id)->where('property_id', $prop_Id)->where('company_id', auth('api')->user()->company_id)->count();
            }
            return response()->json([
                'data' => $data,
                'recurring_invoice' => $recurring_invoice,
                'rent_invoice' => $rent_invoice,
                'tenant' => $tenant,
                'page' => $request->page,
                'sizePerPage' => $request->sizePerPage,
                'length' => $all,
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }
}