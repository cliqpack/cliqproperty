<?php

namespace Modules\Contacts\Http\Controllers\Tenant;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\RecurringInvoice;
use Modules\Properties\Entities\Properties;

class TenantStoreController extends Controller
{
    public function storeRecurringInvoice ($data, $tenant_contact_id, $tenant_folio_id, $property_id, $from) {
        DB::transaction(function () use ($data, $tenant_contact_id, $tenant_folio_id, $property_id, $from) {
            if ($from === 'EDIT') {
                RecurringInvoice::where('property_id', $property_id)->where('tenant_folio_id', $tenant_folio_id)->where('company_id', auth('api')->user()->company_id)->delete();
            }
            $owner_folio_id = Properties::select('owner_folio_id')->where('id', $property_id)->first();
            foreach ($data as $value) {
                $recurring_invoice = new RecurringInvoice();
                $recurring_invoice->details = $value['invoiceDetails'];
                $recurring_invoice->amount = $value['totalInvoiceAmount'];
                $recurring_invoice->include_tax = $value['taxCheckInvoice'];
                $recurring_invoice->chart_of_account_id = $value['invoiceChart'];
                $recurring_invoice->property_id = $property_id;
                if ($value['type'] == 'Owner') {
                    $recurring_invoice->owner_folio_id = $owner_folio_id->owner_folio_id;
                } else {
                    $recurring_invoice->supplier_contact_id = $value['supplier'] ? $value['supplier'] : NULL;
                    $recurring_invoice->supplier_folio_id = $value['supplier_folio_id'] ? $value['supplier_folio_id'] : NULL;
                }
                $recurring_invoice->tenant_contact_id = $tenant_contact_id;
                $recurring_invoice->tenant_folio_id = $tenant_folio_id;
                $recurring_invoice->company_id = auth('api')->user()->company_id;
                $recurring_invoice->save();
            }
        });
    }
}
