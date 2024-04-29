<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Contacts\Entities\RentDiscount;

class RentDiscountController extends Controller
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
        try {
            $attributeNames = array(
                'schedule_for'          => $request->schedule_for,
                'discount_amount'       => $request->discount_amount,
                'tenant_id'           => $request->tc_id,
            );
            $validator = Validator::make($attributeNames, [
                'schedule_for'    => 'required',
                'discount_amount' => 'required',
                'tenant_id'       => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                DB::transaction(function () use ($request,$attributeNames) {
                    $r_d = new RentDiscount();
                    $r_d->schedule_for = $request->schedule_for;
                    $r_d->discount_amount = $request->discount_amount;
                    $r_d->tenant_id = $request->tc_id;
                    $r_d->save();
                    $rentManagement = new RentManagementController();
                    $rentManagement->storeRentDiscount($request->schedule_for, $r_d->id, $request->tc_id, $request->discount_amount);
                    return response()->json(['data' => $r_d, 'message' => 'successful'], 200);
                });
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
}
