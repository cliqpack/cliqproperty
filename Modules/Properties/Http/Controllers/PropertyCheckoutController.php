<?php

namespace Modules\Properties\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Properties\Entities\PropertyCheckoutKey;
use Illuminate\Support\Facades\Validator;

class PropertyCheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index($id)
    {
        // return "hello";
        try {
            $propertiesCheckoutKeys = PropertyCheckoutKey::with('property', 'contact', 'teamMember')->where('property_id', $id)->latest('id')->first();
            return response()->json(['data' => $propertiesCheckoutKeys, 'message' => 'Successful'], 200);
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
        return view('properties::create');
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
                // 'address'               => $request->address,
                'contact_id'             => $request->contact_id ? $request->contact_id : " ",
                'property_id'            => $request->property_id,
                'team_member_id'         => $request->manager_id ? $request->manager_id : " ",
                'return_due'             => $request->return_due,
                'return_time'            => $request->return_time,
                'status'                 => $request->status,
                'note'                   => $request->note ? $request->note : " ",
                'check_type'             => "out",


            );

            $validator = Validator::make($attributeNames, [
                // 'address'             => 'required',
                // 'contact_id'             => 'required',
                'property_id'            => 'required',
                'return_due'             => 'required',
                'return_time'            => 'required',
                // 'note'                   => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $propertyCheckOutKey = PropertyCheckoutKey::create($attributeNames);
                $checkOutKey = new PropertyCheckoutKey();
                $checkOutKey->contact_id = $request->contact_id;
                $checkOutKey->property_id = $request->property_id;
                $checkOutKey->team_member_id = $request->manager_id;
                $checkOutKey->return_due = $request->return_due;
                $checkOutKey->return_time = $request->return_time;
                $checkOutKey->status = $request->status;
                $checkOutKey->note = $request->note;
                $checkOutKey->check_type = "out";
                $checkOutKey->save();

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
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function storeIN(Request $request)
    {
        try {
            $attributeNames = array(
                // 'address'               => $request->address,
                'contact_id'             => $request->contact_id,
                'team_member_id'         => $request->manager_id ? $request->manager_id : " ",
                'property_id'            => $request->property_id,
                'return_due'             => $request->return_due,
                'return_time'            => $request->return_time,
                'note'                   => $request->note,
                'status'                 => $request->status,
                'check_type' => "in",


            );

            $validator = Validator::make($attributeNames, [
                // 'address'             => 'required',
                // 'contact_id'             => 'required',
                'property_id'            => 'required',
                'return_due'             => 'required',
                'return_time'            => 'required',
                // 'note'                   => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                // $propertyCheckOutKey = PropertyCheckoutKey::create($attributeNames);

                $checkOutKey = new PropertyCheckoutKey();
                $checkOutKey->contact_id = $request->contact_id;
                $checkOutKey->property_id = $request->property_id;
                $checkOutKey->team_member_id = $request->manager_id;
                $checkOutKey->return_due = $request->return_due;
                $checkOutKey->return_time = $request->return_time;
                $checkOutKey->note = $request->note;
                $checkOutKey->check_type =  "in";
                $checkOutKey->save();

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
        return view('properties::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('properties::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request)
    {
        try {
            $attributeNames = array(
                // 'address'               => $request->address,
                'contact_id'             => $request->contact_id,
                'team_member_id'         => $request->manager_id,
                'property_id'            => $request->property_id,
                'return_due'             => $request->return_due,
                'return_time'            => $request->return_time,
                'note'                   => $request->note,
            );

            $validator = Validator::make($attributeNames, [
                // 'address'             => 'required',
                // 'contact_id'             => 'required',
                'property_id'            => 'required',
                'return_due'             => 'required',
                'return_time'            => 'required',
                // 'note'                   => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {

                $properties = PropertyCheckoutKey::find($request->checkout_id);
                $properties->update([
                    'return_due'     => $request->return_due,
                    'return_time'    => $request->return_time,
                    'contact_id'     => $request->contact_id,
                    'team_member_id'     => $request->team_member_id,
                    'property_id'    => $request->property_id,
                    'note'           => $request->note,


                ]);
                return response()->json(['data' => null, 'message' => 'successful'], 200);
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
