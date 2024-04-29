<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\UserACL\Entities\Addon;
use Modules\UserACL\Entities\PreRequisiteMenu;

class AddonsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $addons = Addon::where('company_id', auth('api')->user()->company_id)->with('account')->get();
            return response()->json([
                'data' => $addons,
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

    public function activeAddon()
    {
        try {
            $addons = Addon::where('status', 1)->where('company_id', auth('api')->user()->company_id)->with('account')->get();
            return response()->json([
                'data' => $addons,
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
                'display_name' => $request->display_name,
                'charging' => $request->charging,
                'fee_type' => $request->fee_type,
                'value' => $request->value,
                'price' => $request->price,
                'account_id' => $request->account_id,
                'menu_id' => $request->menu_id,
                'status' => $request->status,
                'note' => $request->notes,
                'company_id'    => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                // 'name' => 'required',
                // 'price' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request) {
                    $addon = new Addon();
                    $addon->display_name = $request->display_name;
                    $addon->charging = $request->charging;
                    $addon->fee_type = $request->fee_type;
                    $addon->value = $request->value;
                    $addon->price = $request->price;
                    $addon->account_id = $request->account_id;
                    $addon->menu_id = $request->menu_id;
                    $addon->status = $request->status ? $request->status : false;
                    $addon->frequnecy_type = $request->frequencyType;
                    $addon->time = $request->time;
                    $addon->note = $request->notes;
                    if ($request->frequencyType === 'Weekly') {
                        $addon->weekly = $request->weekName;
                    } elseif ($request->frequencyType === 'Monthly') {
                        $addon->monthly = $request->dayOfMonth;
                    } elseif ($request->frequencyType === 'Yearly') {
                        $addon->yearly = $request->dayOfMonth . '/' . $request->month;
                    }
                    $addon->company_id = auth('api')->user()->company_id;
                    $addon->save();

                    $preRequisiteMenu = new PreRequisiteMenu();
                    $preRequisiteMenu->addon_id = $addon->id;
                    if ($request->status) {
                        $preRequisiteMenu->status = $request->status;
                    } else {
                        $preRequisiteMenu->status = false;
                    }
                    $preRequisiteMenu->company_id = auth('api')->user()->company_id;
                    $preRequisiteMenu->save();
                });
                return response()->json(['message' => 'successful'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
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
        try {
            $attributeNames = array(
                'display_name' => $request->display_name,
                'charging' => $request->charging,
                'fee_type' => $request->fee_type,
                'value' => $request->value,
                'price' => $request->price,
                'account_id' => $request->account_id,
                'menu_id' => $request->menu_id,
                'status' => $request->status,
                'note' => $request->notes,
                'company_id'    => auth('api')->user()->company_id,
            );
            $validator = Validator::make($attributeNames, [
                // 'name' => 'required',
                // 'price' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                DB::transaction(function () use ($request, $id) {
                    Addon::where('id', $id)->where('company_id', auth('api')->user()->company_id)->update([
                        'display_name' => $request->display_name,
                        'charging' => $request->charging,
                        'fee_type' => $request->fee_type,
                        'value' => $request->value,
                        'price' => $request->price,
                        'account_id' => $request->account_id,
                        'menu_id' => $request->menu_id,
                        'status' => $request->status,
                        'frequnecy_type' => $request->frequencyType,
                        'time' => $request->time,
                        'note' => $request->notes,
                        'weekly' => $request->weekName,
                        'monthly' => $request->dayOfMonth,
                        'yearly' => $request->dayOfMonth . '/' . $request->month,
                    ]);
                    PreRequisiteMenu::where('addon_id', $id)->where('company_id', auth('api')->user()->company_id)->update([
                        'status' => $request->status
                    ]);
                });
                return response()->json(['message' => 'successful'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
            ], 500);
        }
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
