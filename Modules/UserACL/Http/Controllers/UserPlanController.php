<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\OwnerPlanAddon;
use Modules\UserACL\Entities\UserPlan;

class UserPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $userPlan = UserPlan::with('plan','user')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'user_plan' => $userPlan,
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
                'plan' => $request->plan,
                'user' => $request->user,
            );
            $validator = Validator::make($attributeNames, [
                'plan' => 'required',
                'user' => 'required',

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $menuPlanDetails = new UserPlan();
                $findMp=$menuPlanDetails->where('user_id',$request->user)->where('company_id', auth('api')->user()->company_id)->get();

                if(count($findMp)==0){
                $menuPlanDetails->menu_plan_id  = $request->plan;
                $menuPlanDetails->user_id       = $request->user;
                $menuPlanDetails->company_id    = auth('api')->user()->company_id;
                $menuPlanDetails->save();
                }else{
                    UserPlan::where("id",$findMp[0]->id)->update([
                        "menu_plan_id"     => $request->plan,
                    ]);
                }
                return response()->json([
                    'message' => 'successful',
                    'menu_plan_details_id' => $menuPlanDetails->id,
                ], 200);
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
            $menuPlan = UserPlan::where("id",$id)->delete();
            return response()->json(['message' => 'successful'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }

    }
    public function menu($id)
    {
        try {
            $owner=OwnerContact::where('property_id',$id)->where('email',auth('api')->user()->email)->first();
            $owner_menu=OwnerPlanAddon::with('addon_menu_check.menu')->where('owner_contact_id',$owner->id)->get();
            // return $owner_menu;
            // $menuPlan = UserPlan::with('plan.details.addon.menu')->where("user_id",auth('api')->user()->id)->get();
            return response()->json(['data'=>$owner_menu,'message' => 'successful'], 200);
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
