<?php

namespace Modules\Accounts\Http\Controllers\RentManagement;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\RecurringInvoice;
use Modules\Accounts\Http\Controllers\DocumentGenerateController;
use Modules\Accounts\Http\Controllers\TaxController;
use Modules\Contacts\Entities\RentManagement;

class RecurringInvoiceController extends Controller
{
    public function generateRecurringInvoice(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $value) {
                    $propAddress = '';
                    $tenantRef = '';
                    $inv_create_date = '';
                    $tenantFolioCode = '';
                    $recurring_invoices = RecurringInvoice::where('company_id', auth('api')->user()->company_id)->where('tenant_contact_id', $value['tenant_id'])->get();
                    foreach ($recurring_invoices as $invoice) {
                        $includeTax = new TaxController();
                        $taxAmount = 0.00;
                        if ($invoice['include_tax']) {
                            $taxAmount = $includeTax->taxCalculation($invoice['amount']);
                        }
                        $store_invoice = new Invoices();
                        $store_invoice->supplier_contact_id      = $invoice['supplier_contact_id'];
                        $store_invoice->invoice_billing_date     = date('Y-m-d');
                        $store_invoice->chart_of_account_id      = $invoice['chart_of_account_id'];
                        $store_invoice->details                  = $invoice['details'];
                        $store_invoice->property_id              = $invoice['property_id'];
                        $store_invoice->amount                   = $invoice['amount'];
                        $store_invoice->file                     = NULL;
                        $store_invoice->include_tax              = $invoice['include_tax'];
                        $store_invoice->tenant_contact_id        = $invoice['tenant_contact_id'];
                        $store_invoice->tenant_folio_id          = $invoice['tenant_folio_id'];
                        $store_invoice->taxAmount                = $taxAmount;
                        $store_invoice->company_id               = $invoice['company_id'];
                        $store_invoice->supplier_folio_id        = $invoice['supplier_folio_id'];
                        $store_invoice->owner_folio_id           = $invoice['owner_folio_id'];
                        $store_invoice->tenant_folio_id          = $invoice['tenant_folio_id'];
                        $store_invoice->rent_management_id       = $value['id'];
                        $store_invoice->save();

                        $get_invoice = Invoices::where('id', $store_invoice->id)->where('company_id', auth('api')->user()->company_id)->with('property.property_address', 'chartOfAccount', 'tenant', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->first();
                        $propAddress = $get_invoice->property->property_address->number . ' ' . $get_invoice->property->property_address->street . ' ' . $get_invoice->property->property_address->suburb . ' ' . $get_invoice->property->property_address->state . ' ' . $get_invoice->property->property_address->postcode;
                        $inv_create_date = Carbon::parse($invoice->created_at)->setTimezone('Asia/Colombo')->toDateString();
                        $dueAmount = $invoice->amount - $invoice->paid;
                        $tenantRef = $get_invoice->tenant->reference;
                        $tenantFolioCode = $get_invoice->tenantFolio->folio_code;
                        $data = [
                            'taxAmount' => $taxAmount,
                            'propAddress' => $propAddress,
                            'invoice_id' => $store_invoice->id,
                            'tenant_folio' => $tenantFolioCode,
                            'tenant_name' => $tenantRef,
                            'created_date' => $inv_create_date,
                            'due_date' => $store_invoice->invoice_billing_date,
                            'amount' => $invoice['amount'],
                            'description' => $invoice['details'],
                            'paid' => 0.00,
                            'dueAmount' => $dueAmount,
                        ];
                        $triggerDocument = new DocumentGenerateController();
                        $triggerDocument->generateInvoiceDocument($data);
                    }

                    $this->recurringInvoiceDocGenerate($value['id'], $propAddress, $tenantFolioCode, $tenantRef, $inv_create_date);
                }
            });
            return response()->json([
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function cancelRecurringInvoice(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->data as $value) {
                    $invoices = Invoices::where('rent_management_id', $value['id'])->where('company_id', auth('api')->user()->company_id)->with('property.property_address', 'chartOfAccount', 'tenant', 'tenantFolio:id,tenant_contact_id,property_id,deposit,money_in,folio_code')->get();
                    foreach ($invoices as $invoice) {
                        if ($invoice['paid'] == 0) {
                            Invoices::where('id', $invoice['id'])->delete();
                        } elseif ($invoice['paid'] > 0 && $invoice['paid'] < $invoice['amount']) {
                            $propAddress = $invoice['property']['property_address']['number'] . ' ' . $invoice['property']['property_address']['street'] . ' ' . $invoice['property']['property_address']['suburb'] . ' ' . $invoice['property']['property_address']['state'] . ' ' . $invoice['property']['property_address']['postcode'];
                            $inv_create_date = Carbon::parse($invoice['created_at'])->setTimezone('Asia/Colombo')->toDateString();
                            $tenantRef = $invoice['tenant']['reference'];
                            $tenantFolioCode = $invoice['tenantFolio']['folio_code'];
                            $data = [
                                'taxAmount' => $invoice['taxAmount'],
                                'propAddress' => $propAddress,
                                'invoice_id' => $invoice['id'],
                                'tenant_folio' => $tenantFolioCode,
                                'tenant_name' => $tenantRef,
                                'created_date' => $inv_create_date,
                                'due_date' => $invoice['invoice_billing_date'],
                                'amount' => $invoice['paid'],
                                'description' => $invoice['details'],
                                'paid' => $invoice['paid'],
                                'dueAmount' => 0.00,
                            ];

                            Invoices::where('id', $invoice['id'])->update([
                                'amount' => $invoice['paid'],
                                'status' => 'Paid'
                            ]);

                            $triggerDocument = new DocumentGenerateController();
                            $triggerDocument->generateInvoiceDocument($data);
                        }
                    }
                    RentManagement::where('id', $value['id'])->update(['recurring_doc' => NULL]);
                }
            });
            return response()->json([
                'status' => "Success"
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $ex->getMessage(), "data" => []]);
        }
    }

    public function recurringInvoiceDocGenerate($id, $propAddress, $tenantFolioCode, $tenantRef, $inv_create_date)
    {
        $rent_management = RentManagement::where('id', $id)
            ->with('invoices')
            ->withSum('invoices as total_invoice_amount', 'amount')
            ->withSum('invoices as total_invoice_paid_amount', 'paid')
            ->withSum('invoices as total_invoice_tax_amount', 'taxAmount')
            ->first();
        $totalTaxAmount = $rent_management->total_invoice_tax_amount;
        $totalAmount = $rent_management->due + $rent_management->total_invoice_amount;
        $totalAmount = $rent_management->due + $rent_management->total_invoice_amount;
        $invoice_total_due = $rent_management->total_invoice_amount - $rent_management->total_invoice_paid_amount;
        $totalDueAmount = ($rent_management->due - $rent_management->received) + $invoice_total_due;
        $totalPaidAmount = $rent_management->received + $rent_management->total_invoice_paid_amount;
        $data = [
            'propAddress' => $propAddress,
            'tenant_folio' => $tenantFolioCode,
            'tenant_name' => $tenantRef,
            'created_date' => $inv_create_date,
            'totalTaxAmount' => $totalTaxAmount,
            'totalAmount' => $totalAmount,
            'totalDueAmount' => $totalDueAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'rent_management' => $rent_management,
            'rent_management_id' => $rent_management->id,
        ];
        $triggerDocument = new DocumentGenerateController();
        $triggerDocument->generateRentManagementDocument($data);
    }
}
