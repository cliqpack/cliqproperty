<?php

namespace Modules\Accounts\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Http\Controllers\RentManagement\RentManagementController;
use Modules\Contacts\Entities\RentManagement;
use Modules\Contacts\Entities\RentReceiptDetail;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Http\Controllers\TenantController;

class DummyController extends Controller
{
    public function dummy()
    {
        $Supplierdisbursement  = SupplierDetails::where('id', 481)->where('company_id', auth('api')->user()->company_id)->with('supplierContact:reference,id', 'supplierPayment')->withSum('total_bills_pending', 'amount')->withSum('total_due_invoice', 'amount')->first();
        return $Supplierdisbursement;
    }
}
