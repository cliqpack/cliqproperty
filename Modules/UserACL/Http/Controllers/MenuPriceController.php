<?php

namespace Modules\UserACL\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UserACL\Entities\MenuPrice;

class MenuPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $menuPrice = MenuPrice::with('menu')->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json([
                'menuPrice' => $menuPrice,
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
                'company_id'    => auth('api')->user()->company_id,
                'price' => $request->price ? $request->price : null,
            );
            $validator = Validator::make($attributeNames, [
                'menu_id' => 'required'

            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $menuPrice = new MenuPrice();
                $findMp=$menuPrice->where('menu_id',$request->menu_id)->where('company_id',auth('api')->user()->company_id)->get();
                
                if(count($findMp)==0){
                    $menuPrice->menu_id     = $request->menu_id;
                    $menuPrice->company_id  = auth('api')->user()->company_id;
                    $menuPrice->price       = $request->price ? $request->price : null;
                    $menuPrice->save();
                }else{
                    MenuPrice::where('id',$findMp[0]->id)->update([
                        "menu_id"     => $request->menu_id,
                        "company_id"  => auth('api')->user()->company_id,
                        "price"       => $request->price ? $request->price : null
                    ]);
                }
                
                return response()->json([
                    'message' => 'successful',
                    'menu_price_id' => $menuPrice->id,
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
            $menuPrice = MenuPrice::where('menu_id',$id)->where('company_id', auth('api')->user()->company_id)->first();
            return response()->json([
                'menuPrice' => $menuPrice,
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
            $menuPrice = MenuPrice::where('id',$id)->delete();
            return response()->json([
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
