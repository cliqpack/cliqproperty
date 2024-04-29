<?php

namespace App\Http\Controllers\Menu;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Module\ModuleController;
use App\Models\Menu;
use App\Models\Module\ModuleModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class MenuController extends Controller
{
    //module view
    public function index()
    {
        
       
        $menus = Menu::all()->where('soft_delete', 0);

        $data = [
            'menus' => $menus
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
       
       // $userName = Auth::user()->name;
        $userName = "Himel";
        $defaultValue = 0;

        $attributeNames = array(
            'menu_title'        => $request->menu_title,
            'slug'              => $request->slug,
            // 'created_by'        => $userName,
            // 'soft_delete'       => $defaultValue,
            'parent'            => $request->parent_id,
            'sort_order'        => $request->sort_order
        );

        $validator = Validator::make($attributeNames, [
            'menu_title'        => 'required',
            'slug'              => 'required',
            // 'created_by'        => 'required',
            // 'soft_delete'       => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()),422);
        } else {
            //work goes here
            $menu = Menu::create($attributeNames);
            $customRequest = Request();
            $menuTitle = $request->menu_title;
            for($i =0 ; $i<4;$i++){
                if($i==0){
                    $extension = '-Add';
                }
                else if($i==1){
                    $extension = '-View';
                }
                else if($i==2){
                    $extension = '-Edit';


                }
                else{
                    $extension = '-Delete';

                }
                $customRequest->replace(
                    [
                        'name'    => $menuTitle.$extension,
                        'menu_id' => $menu->id,
                    ]
                    );
    
                $moduleController = new ModuleController;
                $moduleController->store($customRequest);

            }
            
            return response()->json(['data' => null, 'message' => 'Successful']);
        }
    }


    public function destroy(Request $request){
        $id = $request->menu;

        $menu = Menu::findOrFail($id)->delete();

        return response()->json(["success"]);


    }

     public function getModules(Request $request){
        $id = $request->menu_id;

        $modules = ModuleModel::where('menu_id',$id)->get();

        return response()->json($modules);

    }





}
