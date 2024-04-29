<?php

namespace App\Http\Controllers\module;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Module\ModuleModel;
use App\Models\Module\ModuleDetailsModel;

class ModuleController extends Controller
{
    //module view
    public function index()
    {
        $modules = ModuleModel::all('id', 'name', 'created_by', 'soft_delete')->where('soft_delete', 0);

        $data = [
            'modules' => $modules
        ];
        return response()->json($data);
    }

    //inserting module
    public function store(Request $request)
    {
        $userName = auth('api')->user();

       
        
        $defaultValue = 0;

        $attributeNames = array(
            'name'              => $request->name,
            'menu_id'           => $request->menu_id,
            'created_by'        => $userName->first_name,
            'soft_delete'       => $defaultValue
        );

        $validator = Validator::make($attributeNames, [
            'name'              => 'required',
            'created_by'        => 'required',
            'soft_delete'       => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        } else {
            //work goes here
            ModuleModel::create($attributeNames);
            return response()->json("Success");
        }
    }

     //  update module

     public function update($id)
     {  
       
         $module = ModuleModel::findOrFail($id);
     
         $attributeNames = array(
             'name'    => request()->name,
         );
 
 
         $validator = Validator::make($attributeNames, [
             'name'    => 'required',
         ]);
 
         if ($validator->fails()) {
             return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
         } else {
 
             $module->update($attributeNames);
             return response()->json("Updated!");
         }
     }


      //delete module
    public function destroy($id)
    {

        $module = ModuleModel::findOrFail($id);
        $module->delete();
        //deleting the related routes
        $ids = ModuleDetailsModel::where('module_id', $id)->pluck('id')->toarray();
        ModuleDetailsModel::whereIn('id', $ids)->delete();

        return response()->json("Deleted",200);
    }

    //module setup view
    public function moduleSetup()
    {
        $modules = ModuleModel::all('id', 'name', 'soft_delete')->where('soft_delete', 0);

        $data = [
            'modules' => $modules
        ];

        return response()->json($data);

    }


    //get single module 
    public function getModule($id)
    {
        $module = ModuleModel::findOrFail($id);
        return response()->json($module);
    }


    //module Insert
    public function moduleDetailsInsertAjax(Request $request)
    {
        $userName = auth('api')->user()->first_name;
        $defaultValue = 0;

        $attributeNames = array(
            'module_id'         => $request->module,
            'route'             => $request->route,
            'created_by'        => $userName,
            'soft_delete'       => $defaultValue
        );
      
        $validator = Validator::make($attributeNames, [
            'module_id'         => 'required',
            'route'             => 'required',
            'created_by'        => 'required',
            'soft_delete'       => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        } else {
            //work goes here
            ModuleDetailsModel::create($attributeNames);
            return response()->json("Success");
        }
    }

   

   


    public function getRouteByModule(Request $request)
    {
        $id = $request->id;
      
        //$modulesRoutes = ModuleDetailsModel::all('id','module_id','route','status')->where('status',0)->where('module_id',$id);
        $modulesRoutes = ModuleDetailsModel::select('id', 'module_id', 'route', 'soft_delete')->where(
            'module_id',
            $id
        )->where('soft_delete', 0)->get();
        return response()->json($modulesRoutes, 200);
    }


    //module Details View

    public function moduleRouteView()
    {

        $modules = ModuleModel::select('id', 'name', 'soft_delete')->where('soft_delete', 0)->get();
        $modulesRoutes = ModuleDetailsModel::select('id', 'module_id', 'created_by', 'route','soft_delete')->where('soft_delete', 0)->with('module')->get();
        //dd($modulesRoutes);
        $data = [
            'moduleRoutes'      => $modulesRoutes,
            'modules'           => $modules
        ];
        return response()->json($data);
    }


    //get single module
    public function getModuleDetailsByidAjax(Request $request){
        $id = $request->id;
        $module = ModuleDetailsModel::findOrFail($id);
        return response()->json($module);
    }

//update
    public function moduleDerailsUpdateAjax(Request $request)
    {
        $id = $request->id;
        $module = ModuleDetailsModel::findOrFail($id);
        $userName = Auth::user()->name;
        $defaultValue = 0;

        $attributeNames = array(
            'module_id'                     => $request->module,
            'route'                         => $request->route,
            'created_by'                    => $userName,
            'soft_delete'                   => $defaultValue
        );

        $validator = Validator::make($attributeNames, [
            'module_id'                 => 'required',
            'route'                     => 'required',
            'created_by'                => 'required',
            'soft_delete'               => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        } else {
            //work goes here
            $module->update($attributeNames);
            return response()->json("Success");
        }
    }

    //delete
    public function moduleDetailsDeleteAjax(Request $request)
    { 
        $id = $request->id;
        // dd($id);
        $module = ModuleDetailsModel::findOrFail($id);
        $deletedAttribute = 1;
        $attributeNames = array(
        'soft_delete' => $deletedAttribute
        );
        $module->update($attributeNames);
        return response()->json("Success");
    }
    
}
