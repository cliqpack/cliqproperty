<?php

namespace Modules\Contacts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Contacts\Entities\ContactLabel;

class ContactLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {

            $contactIds = json_decode($request->contact_id, true);
            $labels = json_decode($request->labels, true);


            if (is_array($contactIds)) {

                $contactLabel = ContactLabel::whereIn('contact_id', $contactIds)->get();

                return response()->json(['data' => $contactLabel, 'message' => 'successful'], 200);
            } else {

                return response()->json(['status' => false, 'message' => 'Invalid contact_id format'], 400);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }
    public function updateLabels(Request $request)
    {
        try {
            $contactIds = json_decode($request->contact_id, true);
            $newLabels = json_decode($request->labels, true);
            $removedLabels = json_decode($request->removed_labels, true);


            foreach ($contactIds as $contactId) {
                foreach ($newLabels as $label) {
                    $exists = ContactLabel::where('contact_id', $contactId)
                        ->where('labels', $label)
                        ->exists();

                    if (!$exists) {
                        ContactLabel::create([
                            'contact_id' => $contactId,
                            'labels' => $label,
                        ]);
                    }
                }
            }

            foreach ($contactIds as $contactId) {
                foreach ($removedLabels as $label) {
                    ContactLabel::where('contact_id', $contactId)
                        ->where('labels', $label)
                        ->delete();
                }
            }

            return response()->json(['message' => 'Labels updated successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }







    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('contacts::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $contactLabel = array(
                'contact_id' => $request->contact_id,
                'labels' => $request->labels
            );
            $validator = Validator::make($contactLabel, [
                'contact_id'
            ]);
            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
            } else {
                $contactLabel = ContactLabel::where('contact_id', $request->contact_id)->first();
                if ($contactLabel) {
                    ContactLabel::where('contact_id', $request->contact_id)->delete();
                    foreach ($request->labels as $label) {
                        $contactLabel = new ContactLabel();
                        $contactLabel->contact_id = $request->contact_id;
                        $contactLabel->labels = $label;
                        $contactLabel->save();
                    }
                } else {
                    foreach ($request->labels as $label) {
                        $contactLabel = new ContactLabel();
                        $contactLabel->contact_id = $request->contact_id;
                        $contactLabel->labels = $label;
                        $contactLabel->save();
                    }
                }

                return response()->json(['contact_id' => $request->contact_id, 'message' => 'successfull'], 200);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('contacts::edit');
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
