<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\ReconcilliationMonths;

class ReconciliationReversalController extends Controller
{
    /**
     * This function checks if a reconciliation for a given date is approved.
     * It returns a JSON response indicating the approval status.
     * If any exception occurs during the process, it returns a 500 error response with the exception message.
     *
     * @param  \Illuminate\Http\Request  $request - The request object containing the date to check for approval.
     * @return \Illuminate\Http\JsonResponse - A response indicating the approval status or an error response with exception details.
     */
    public function approvedReconciliation(Request $request)
    {
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
