<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\ReconcilliationMonths;

class ReconciliationReversalController extends Controller
{
    public function approvedReconciliation (Request $request) {
        try {
            $status = false;
            $reconciliationApproved = ReconcilliationMonths::select('reconciliation_status')->where('date', 'LIKE', '%' . $request->date . '%')->where('company_id', auth()->user()->company_id)->first();
            if ($reconciliationApproved->reconciliation_status === "approved") {
                $status = true;
            }
            return response()->json([
                'status' => $status,
                'message' => 'Successful'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []], 500);
        }
    }
}
