<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\UserACL\Entities\OwnerPlan;

class PlanController extends Controller
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


    public function ownerPlanAddons(Request $request, $folio_id)
    {
        try {
            $folio = OwnerFolio::where('id', $folio_id)->where('company_id', auth('api')->user()->company_id)->first();
            $ownerPlan = OwnerPlan::where('owner_id', $folio->owner_contact_id)->where('property_id', $request->proId)->where('company_id', auth('api')->user()->company_id)->first();
            $ownerPlanAddon = [];
            if (!empty($ownerPlan)) {
                $ownerPlanAddon = OwnerPlanAddon::where('plan_id', $ownerPlan->menu_plan_id)->where('property_id', $request->proId)->with('addon','ownerFolio', 'ownerContact')->where('company_id', auth('api')->user()->company_id)->get();
            }
            return response()->json([
                'ownerPlanAddon' => $ownerPlanAddon,
                'ownerPlan' => $ownerPlan,
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
}
