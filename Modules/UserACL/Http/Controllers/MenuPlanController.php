<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\UserACL\Entities\MenuPlan;
use Modules\UserACL\Entities\MenuPlanDetail;
use Modules\UserACL\Entities\UserPlan;

class MenuPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $menuPlan = MenuPlan::with('details.menu', 'details.addon')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'menu_plan' => $menuPlan,
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

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('useracl::create');
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
                'name' => $request->name,
                'desc_details' => $request->details,
                'price' => $request->price,
                'frequency_type' => $request->frequency_type,
                'company_id'    => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                'name' => 'required',
                'price' => 'required',
                'frequency_type' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($attributeNames, $request) {
                    $menuPlan = MenuPlan::create($attributeNames);
                    foreach ($request->menus as $value) {
                        $menuPlanDetails = new MenuPlanDetail();
                        $menuPlanDetails->menu_plan_id     = $menuPlan->id;
                        // $menuPlanDetails->menu_id       = $value;
                        $menuPlanDetails->addon_id       = $value;
                        $menuPlanDetails->save();
                    }
                });
                return response()->json(['message' => 'successful'], 200);
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
        return view('useracl::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('useracl::edit');
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
            $checkUserPlan = UserPlan::where('menu_plan_id', $id)->get();
            if (count($checkUserPlan) == 0) {
                $menuPlan = MenuPlan::where('id', $id)->delete();
                return response()->json(['message' => 'successful'], 200);
            } else {
                return response()->json([
                    "status" => "error",
                    "error" => "Some User Already Use This Plan",
                    "message" => 'successful'
                ], 500);
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
}
