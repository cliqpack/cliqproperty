<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UserACL\Entities\PreRequisiteMenu;
use Modules\UserACL\Entities\PreRequisiteMenuDetail;

class PreRequisiteMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            // $preRequisiteMenu = PreRequisiteMenu::with('menu', 'preRequisiteMenuDetail', 'preRequisiteMenuDetail.preMenu')->where('company_id', auth('api')->user()->company_id)->get();
            $preRequisiteMenu = PreRequisiteMenu::where('status', 1)->with('addon', 'preRequisiteMenuDetail', 'preRequisiteMenuDetail.addon')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'preRequisiteMenu' => $preRequisiteMenu,
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
                // 'menu_id' => $request->menu_id,
                'addon_id' => $request->addon_id,

            );
            $validator = Validator::make($attributeNames, [
                // 'menu_id' => 'required'
                'addon_id' => 'required'

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $preRequisiteMenu = new PreRequisiteMenu();
                // $findPrm=$preRequisiteMenu->where('menu_id',$request->menu_id)->where('company_id',auth('api')->user()->company_id)->get();
                $findPrm=$preRequisiteMenu->where('addon_id',$request->addon_id)->where('company_id',auth('api')->user()->company_id)->get();
                $pr_id=null;
                
                if(count($findPrm) == 0){
                    // $preRequisiteMenu->menu_id     = $request->menu_id;
                    $preRequisiteMenu->addon_id     = $request->addon_id;
                    $preRequisiteMenu->status     = 1;
                    $preRequisiteMenu->company_id     =auth('api')->user()->company_id;
                    $preRequisiteMenu->save();
                    $pr_id=$preRequisiteMenu->id;  
                } else if(count($findPrm) > 0) {
                    $pr_id=$findPrm[0]->id;
                    PreRequisiteMenuDetail::where('pre_req_id',$pr_id)->delete();
                }
                
                foreach ($request->prm as $value) {
                    $preRequisiteDetail = new PreRequisiteMenuDetail();
                    $preRequisiteDetail->pre_req_id  = $pr_id;
                    // $preRequisiteDetail->menu_id     = $value["value"];
                    $preRequisiteDetail->addon_id     = $value["value"];
                    $preRequisiteDetail->save();
                }

                return response()->json([
                    'message' => 'successful',
                    'pre_requisite_menu_id' => $preRequisiteMenu->id,
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
        try {
            $preRequisiteMenu = PreRequisiteMenu::where('id', $id)->where('company_id', auth('api')->user()->company_id)->with('preRequisiteMenuDetail')->get();
            return response()->json([
                'preRequisiteMenu' => $preRequisiteMenu,
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
            PreRequisiteMenu::where('id', $id)->delete();
            // PreRequisiteMenuDetail::where('pre_req_id', $id)->delete();
            return response()->json([
                'preRequisiteMenu' => $id,
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
