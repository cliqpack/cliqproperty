<?php

namespace Modules\Accounts\Http\Controllers\OwnerStatements;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Contacts\Entities\OwnerFolio;
use Modules\Properties\Entities\PropertyDocs;

class OwnerStatementsController extends Controller
{
    public function ownerStatements($folio_id, $property_id)
    {
        try {
            $owner_folio = OwnerFolio::select('owner_contact_id')->where('id', $folio_id)->first();
            $statements = PropertyDocs::where('property_id', $property_id)->where('owner_id', $owner_folio->owner_contact_id)->where('company_id', auth('api')->user()->company_id)->get();
            return response()->json(['data' => $statements, 'message' => 'Successful']);
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
