<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Contact;

class ContactController extends Controller
{

    public function contacts(){
        $contacts = Contact::all();
        return response()->json($contacts);
    }

    public function contactStore(Request $request)
    {
        $attributeNames = array(
            'reference'             => $request->reference,
            'first_name'            => $request->first_name,
            'last_name'              => $request->last_name,
            'salutation'         => $request->salutation,
            'company_name'          => $request->company_name,
            'mobile_phone'           => $request->mobile_phone,
            'work_phone'            => $request->work_phone,
            'email'            => $request->email,
            'communication'             => $request->communication,
            'abn'             => $request->abn,
            'notes'            => $request->notes,
        );

        $validator = Validator::make($attributeNames, [
            'reference'             => 'required',
            'first_name'            => 'required',
            'last_name'            => 'required',
            'salutation'              => 'required',
            'company_name'           => 'required',
            'mobile_phone'            => 'required',
            'work_phone'            => 'required',
            'email'             => 'required',
            'communication'             => 'required',
            'abn'            => 'required',
            'notes'     => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()),422);
        } else {
            $contact = Contact::create($attributeNames);
            return response()->json(['message' => 'successful']);
        }
    }

    public  function update(Request $request, $id)
    {
        $attributeNames = array(
            'reference'             => $request->reference,
            'first_name'            => $request->first_name,
            'last_name'              => $request->last_name,
            'salutation'         => $request->salutation,
            'company_name'          => $request->company_name,
            'mobile_phone'           => $request->mobile_phone,
            'work_phone'            => $request->work_phone,
            'email'            => $request->email,
            'communication'             => $request->communication,
            'abn'             => $request->abn,
            'notes'            => $request->notes,
        );

        $validator = Validator::make($attributeNames, [
            'reference'           => 'required',
            'first_name'          => 'required',
            'last_name'       => 'required',
            'salutation'        => 'required',
            'company_name'         => 'required',
            'mobile_phone'          => 'required',
            'work_phone'          => 'required',
            'email'           => 'required',
            'communication'           => 'required',
            'abn'          => 'required',
            'notes'   => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()),422);
        } else {
                $contact= Contact::find($id);
                $contact->update([
                    'reference'             => $request->reference,
                    'first_name'            => $request->first_name,
                    'last_name'              => $request->last_name,
                    'salutation'         => $request->salutation,
                    'company_name'          => $request->company_name,
                    'mobile_phone'           => $request->mobile_phone,
                    'work_phone'            => $request->work_phone,
                    'email'            => $request->email,
                    'communication'             => $request->communication,
                    'abn'             => $request->abn,
                    'notes'            => $request->notes,
                ]);
            return response()->json(['data' => null, 'message' => 'successful']);
          }

    }
}
