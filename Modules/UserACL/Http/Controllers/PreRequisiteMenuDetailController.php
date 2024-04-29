<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UserACL\Entities\PreRequisiteMenu;
use Modules\UserACL\Entities\PreRequisiteMenuDetail;

class PreRequisiteMenuDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $preRequisiteMenuDetail = PreRequisiteMenuDetail::get();
            return response()->json([
                'pre_requisite_menu_detail' => $preRequisiteMenuDetail,
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
                'menu_id' => $request->menu_id,
                'pre_req_id' => $request->pre_req_id,

            );
            $validator = Validator::make($attributeNames, [
                'menu_id' => 'required'

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $preRequisiteDetail = new PreRequisiteMenuDetail();
                $preRequisiteDetail->pre_req_id  = $request->pre_req_id;
                $preRequisiteDetail->menu_id     = $request->menu_id;
                $preRequisiteDetail->save();
                return response()->json([
                    'message' => 'successful',
                    'pre_req_menu_details_id' => $preRequisiteDetail->id,
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
        //
    }
}
