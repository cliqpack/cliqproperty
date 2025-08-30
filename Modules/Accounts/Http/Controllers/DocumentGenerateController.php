<?php

namespace Modules\Accounts\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\Bill;
use Modules\Accounts\Entities\GeneratedWithdrawal;
use Modules\Accounts\Entities\Invoices;
use Modules\Accounts\Entities\Receipt;
use Modules\Inspection\Entities\InspectionTaskMaintenanceDoc;
use Modules\Messages\Http\Controllers\MessageWithMailController;
use Modules\Properties\Entities\PropertyActivity;
use Modules\Properties\Entities\PropertyActivityEmail;
use Modules\Properties\Entities\PropertyDocs;
use Modules\Settings\Entities\SettingBrandStatement;
use Modules\Settings\Entities\BrandSettingLogo;
use Modules\Settings\Entities\CompanySetting;
use Modules\Contacts\Entities\OwnerContact;
use Modules\Contacts\Entities\RentManagement;
use stdClass;

class DocumentGenerateController extends Controller
{
    public function __construct() {}

    public function generateBatchDocument($data)
    {
        $pdf = PDF::loadView('accounts::generateBatch', $data);
        $filename = 'generated_batch_' . $data['id'];
        // $pdf->save(public_path('public/Document') . '/' . $filename . '.pdf');
        $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';
        $filename_s3 = Storage::disk('s3')->put($path, $pdf->output());
        GeneratedWithdrawal::where('id', $data['id'])->update([
            'doc_path' => $filename_s3 ? $path : null
        ]);
    }

    public function generateJobWorkOrderDocument($data)
    {
        $statementName = InspectionTaskMaintenanceDoc::latest()->first();
        $pdf = PDF::loadView('maintenance::work_order', $data);
        $content = $pdf->download()->getOriginalContent();
        if (!empty($statementName)) {
            $filename = 'work_order-' . ($statementName->id + 1);
        } else
            $filename = 'work_order-1';

        // $path = config('app.asset_s') . '/Document' . '/' . $filename . '.pdf';
        $path = config('app.asset_s') . '/Document' . '/' . $filename . '.pdf';
        $filename_s3 = Storage::disk('s3')->put($path, $pdf->output());

        $uploadDoc = new InspectionTaskMaintenanceDoc();
        $uploadDoc->doc_path = $filename_s3 ? $path : null;
        $uploadDoc->property_id = $data['property_id'];
        $uploadDoc->job_id = $data['job_id'];
        $uploadDoc->name = $filename;
        $uploadDoc->inspection_id = NULL;
        $uploadDoc->task_id = NULL;
        $uploadDoc->generated = 'Generated';
        $uploadDoc->company_id = auth('api')->user()->company_id;
        $uploadDoc->save();
        // Storage::put('public/Document/' . $filename . '.pdf', $content);
    }

    public function generateInvoiceDocument($data)
    {
        $pdf = PDF::loadView('accounts::invoicePdf', $data);
        $filename = 'invoice-' . $data['invoice_id'];
        $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';
        $filename_s3 = Storage::disk('s3')->put($path, $pdf->output());
        Invoices::where('id', $data['invoice_id'])->update(['doc_path' => $filename_s3 ? $path : null]);

        // Return file details: path, name, and type
        return [
            'file_path' => $filename_s3 ? $path : null,
            'file_name' => $filename,
            'file_type' => '.pdf'
        ];
    }

    public function generateRentManagementDocument($data)
    {
        $pdf = PDF::loadView('accounts::rentManagementPdf', $data);
        $filename = $data['tenant_folio'] . '-RentInvoice000' . $data['rent_management_id'];
        $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';
        $filename_s3 = Storage::disk('s3')->put($path, $pdf->output());
        RentManagement::where('id', $data['rent_management_id'])->update(['recurring_doc' => $filename_s3 ? $path : null]);
    }

    public function generateDisbursementDocument($data)
    {
        $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
        $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
        $data['brandStatement'] = $brandStatement;
        $data['brandLogo'] = $brandLogo;
        $data['user'] = $user;
        $data['company'] = $company;

        $body = "You have received a disbursement from MyDay. Disbursement amount is $" . $data['payout']->amount . ".";
        $statementName = PropertyDocs::latest()->first();
        $dompdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf->setOptions($options);
        $html = null;
        $ownerEmail = $data['to'];
        $language = User::where('email', $ownerEmail)->pluck('language_code')->first();
        if ($language !== null) {
            if ($language === 'en') {
                $html = view('accounts::receiptPdf', $data)->render();
            } elseif ($language === 'cn') {
                $html = view('accounts::receiptPdfMandarin', $data)->render();
            } else {
                $html = view('accounts::receiptPdf', $data)->render();
            }
        } else {
            $html = view('accounts::receiptPdf', $data)->render();
        }



        if (!empty($statementName)) {
            $filename = 'Statement-' . ($statementName->id + 1);
        } else
            $filename = 'Statement-1';
        // $pdf->save(public_path('public/Document') . '/' . $filename . '.pdf');
        $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';
        $filename_s3 = Storage::disk('s3')->put($path, $html);
        $uploadDoc = new PropertyDocs();
        $uploadDoc->doc_path = $filename_s3 ? $path : null;
        $uploadDoc->property_id = $data['owner_contacts']['property_id'];
        $uploadDoc->name = $filename;
        $uploadDoc->file_size = NULL;
        $uploadDoc->generated = 'Generated';
        $uploadDoc->contact_id = $data['owner_contacts']['contact_id'];
        $uploadDoc->owner_id = $data['owner_contacts']['id'];
        $uploadDoc->company_id = auth('api')->user()->company_id;

        $uploadDoc->save();

        $trigger = new MessageWithMailController();
        $dataa = new stdClass();
        $dataa->property_id = $data['property_id'];
        $dataa->to = $data['to'];
        $dataa->from = auth('api')->user()->email;
        $dataa->subject = 'Disbursement Attachment';
        $dataa->body = $body;
        $dataa->filename_s3 = $filename_s3 ? $path : null;
        $dataa->filename = $filename;
        $dataa->extension = '.pdf';
        $dataa->attached = [["path" => $path]];
        $trigger->attachmentMail($dataa);

        $propertyActivity = new PropertyActivity();
        $propertyActivity->property_id = $data['property_id'];
        // $propertyActivity->disbursement_id = $bill;
        $propertyActivity->status = "Disbursement Complete";
        $propertyActivity->type = "Created";
        // $propertyActivity_email->type = "email";
        $propertyActivity->save();
        $propertyActivity_email = new PropertyActivityEmail();
        $propertyActivity_email->property_activity_id = $propertyActivity->id;
        // $propertyActivity_email->bill_id = $data['bill_id'];
        $propertyActivity_email->type = "email";
        $propertyActivity_email->email_body = $body;
        $propertyActivity_email->email_from = auth('api')->user()->email;
        $propertyActivity_email->email_to = $data['to'];
        $propertyActivity_email->subject = 'Disbursement Attachment';
        $propertyActivity_email->save();
    }

    public function generateDisbursementPreview($data)
    {
        $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
        $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
        $user = User::where('company_id', auth('api')->user()->company_id)->first();
        $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
        $data['brandStatement'] = $brandStatement;
        // $data['brandLogo'] = $brandLogo;
        $data['brandLogo'] = $brandLogo ? $brandLogo : null;
        $data['user'] = $user;
        $data['company'] = $company;
        // return $data;
        $statementName = PropertyDocs::latest()->first();
        $pdf = PDF::loadView('accounts::receiptPreviewPdf', $data);
        // return $pdf;
        $content = $pdf->download()->getOriginalContent();
        if (!empty($statementName)) {
            $filename = 'Statement-' . ($statementName->id + 1);
        } else
            $filename = 'Statement-1';
        return $pdf->download($filename . '.pdf');
    }

    public function generateBill($data)
    {

        $bill = DB::transaction(function () use ($data) {
            // return $data;
            $body = "This is an email to remind you that a bill of $" . $data['amount'] . " has been generated in your name and the bill will be deducted in the next disbursement";
            $pdf = PDF::loadView('accounts::billPdf', $data);
            $filename = 'bill-' . $data['bill_id'];
            $pdf->download()->getOriginalContent();
            $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . $filename . '.pdf';
            $filename_s3 = Storage::disk('s3')->put($path, $pdf->output());
            Bill::where('id', $data['bill_id'])->update(['doc_path' => $path]);
            if ($data['approved'] === true) {
                $triggerBillMail = new MessageWithMailController();
                $dataa = new stdClass();
                $dataa->property_id = $data['property_id'];
                $dataa->to = $data['to'];
                $dataa->from = auth('api')->user()->email;
                $dataa->subject = 'Bill Attachment';
                $dataa->body = $body;
                $dataa->filename_s3 = $filename_s3 ? $path : null;
                $dataa->filename = $filename;
                $dataa->extension = '.pdf';
                $dataa->attached = [["path" => $path]];

                $triggerBillMail->attachmentMail($dataa);
            }
        });
        return $bill;
    }
//miraz part
    // public function generateReceiptDocument($receipt_id, $pay_type, $onBehalf, $totalTaxAmount)
    // {
    //     $receipt = Receipt::where('id', $receipt_id)->where('company_id', auth('api')->user()->company_id)->with('property:id,reference', 'property.property_address', 'receipt_details', 'company')->first();
    //     $pushPropertyAddress = new stdClass();
    //     $receiptInformation = new stdClass();
    //     $footerDetails = new stdClass();
    //     $receiptData = new stdClass();
    //     $receiptDetails = [];
    //     $receiptDetails = $receipt->receipt_details;

    //     $propAddress = NULL;
    //     if ($receipt->property) {
    //         $propAddress = $receipt->property->property_address->number . ' ' . $receipt->property->property_address->street . ' ' . $receipt->property->property_address->suburb . ' ' . $receipt->property->property_address->state . ' ' . $receipt->property->property_address->postcode;
    //     }
    //     $pushPropertyAddress->name = 'Address';
    //     $pushPropertyAddress->value = $propAddress;

    //     $receiptData->totalAmount = $receipt->amount;
    //     $receiptData->totalTaxAmount = $totalTaxAmount;

    //     $receiptInformation->receiptNumber = $receipt_id;
    //     $receiptInformation->receiptDate = $receipt->receipt_date;
    //     $receiptInformation->onBehalfOf = $onBehalf;
    //     $receiptInformation->folioInfo = 'Folio';
    //     $receiptInformation->folioName = $receipt->receipt_details[0]['contact_reference']['reference'] . ' - ' . $receipt->receipt_details[0]['folioCode'];

    //     $footerDetails->payment_method = strtoupper($pay_type);
    //     $footerDetails->principal = $receipt->created_by;
    //     $footerDetails->company = $receipt->company->company_name;
    //     $footerDetails->receipt_by = $receipt->created_by;
    //     $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
    //     $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
    //     $user = User::where('company_id', auth('api')->user()->company_id)->first();
    //     $company = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();
    //     $data['brandStatement'] = $brandStatement;
    //     $data['brandLogo'] = $brandLogo;
    //     $data['user'] = $user;
    //     $data['company'] = $company;

    //     $data = [
    //         'property_address' => $pushPropertyAddress,
    //         'receiptInformation' => $receiptInformation,
    //         'footerDetails' => $footerDetails,
    //         'receiptDetails' => $receiptDetails,
    //         'receiptData' => $receiptData,
    //     ];

    //     $pdf = PDF::loadView('accounts::transactionReceiptPdf', $data);
    //     $pdf->download()->getOriginalContent();
    //     $path = config('app.asset_s') . '/Document' . '/' . date('YmdHi') . 'receipt' . '.pdf';
    //     Storage::disk('s3')->put($path, $pdf->output());
    //     $receipt->totalTaxAmount = $totalTaxAmount;
    //     $receipt->doc_path = $path;
    //     $receipt->save();

    //     // Return file details: path, name, and type
    //     return [
    //         'file_path' =>  $path,
    //         'file_name' => "receipt",
    //         'file_type' => '.pdf'
    //     ];
    // }
   public function generateReceiptDocument($receipt_id, $pay_type, $onBehalf, $totalTaxAmount)
{
    $receipt = Receipt::where('id', $receipt_id)
        ->where('company_id', auth('api')->user()->company_id)
        ->with('property:id,reference', 'property.property_address', 'receipt_details', 'company')
        ->first();

    if (!$receipt) {
        throw new \Exception('Receipt not found');
    }

    // Initialize objects
    $pushPropertyAddress = new stdClass();
    $receiptInformation = new stdClass();
    $footerDetails = new stdClass();
    $receiptData = new stdClass();
    
    // Get receipt details
    $receiptDetails = $receipt->receipt_details;

    // Build property address dynamically
    $propAddress = NULL;
    if ($receipt->property && $receipt->property->property_address) {
        $address = $receipt->property->property_address;
        $addressParts = array_filter([
            $address->number,
            $address->street,
            $address->suburb,
            $address->state,
            $address->postcode
        ]);
        $propAddress = implode(' ', $addressParts);
    }
    
    $pushPropertyAddress->name = 'Address';
    $pushPropertyAddress->value = $propAddress;

    // Receipt totals
    $receiptData->totalAmount = $receipt->amount;
    $receiptData->totalTaxAmount = $totalTaxAmount;

    // Receipt information
    $receiptInformation->receiptNumber = $receipt_id;
    $receiptInformation->receiptDate = $receipt->receipt_date;
    $receiptInformation->onBehalfOf = $onBehalf;
    $receiptInformation->folioInfo = 'Folio';
    
    // Build folio name safely
    $folioName = '';
    if (!empty($receipt->receipt_details) && 
        isset($receipt->receipt_details[0]['contact_reference']['reference']) && 
        isset($receipt->receipt_details[0]['folioCode'])) {
        $folioName = $receipt->receipt_details[0]['contact_reference']['reference'] . ' - ' . $receipt->receipt_details[0]['folioCode'];
    }
    $receiptInformation->folioName = $folioName;

    // Footer details
    $footerDetails->payment_method = strtoupper($pay_type);
    $footerDetails->principal = $receipt->created_by;
    $footerDetails->company = $receipt->company->company_name ?? 'N/A';
    $footerDetails->receipted_by = $receipt->created_by; // Fixed typo from receipt_by

    // Get dynamic company settings
    $brandStatement = SettingBrandStatement::where('company_id', auth('api')->user()->company_id)->first();
    $brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();
    $user = User::where('company_id', auth('api')->user()->company_id)->first();
    $companySetting = CompanySetting::where('company_id', auth('api')->user()->company_id)->first();

    // Build company object with all dynamic data and defaults
    $company = new stdClass();
    $company->phone = $companySetting->phone ?? $receipt->company->phone ?? '(000) 000-0000';
    $company->website = $companySetting->website ?? $receipt->company->website ?? 'www.company.com';
    $company->email = $user->email ?? $companySetting->company->email ?? 'info@company.com';
    $company->abn = $companySetting->abn ?? $companySetting->abn  ?? '00 000 000 000';
    $company->license = $companySetting->licence_number ?? $companySetting->licence_number ?? 'LIC000000';
    
    // Build address from company settings with defaults
    $companyAddressParts = array_filter([
        $companySetting->address ?? $receipt->company->address ?? null,
        $companySetting->city ?? $receipt->company->city ?? null,
        $companySetting->state ?? $receipt->company->state ?? null,
        $companySetting->postcode ?? $receipt->company->postcode ?? null
    ]);
    
    if (empty($companyAddressParts)) {
        $company->address = user->address ?? "123 Business Street\nCity, State 12345";
    } else {
        $company->address = implode(', ', $companyAddressParts);
    }

    $logoData = null;
$brandLogo = BrandSettingLogo::where('company_id', auth('api')->user()->company_id)->first();

if ($brandLogo && $brandLogo->brand_image) {
    try {
        // Get the full S3 URL
        $imageUrl = Storage::disk('s3')->url($brandLogo->brand_image);
        
        // Add debugging
        \Log::info('Attempting to load logo from: ' . $imageUrl);
        
        // Create a context for file_get_contents with timeout and user agent
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP PDF Generator)'
            ]
        ]);
        
        // Try to get image content
        $imageContent = @file_get_contents($imageUrl, false, $context);
        
        if ($imageContent !== false) {
            // Get image info
            $imageInfo = @getimagesizefromstring($imageContent);
            
            if ($imageInfo !== false) {
                $mimeType = $imageInfo['mime'];
                
                // Convert to base64
                $logoData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                \Log::info('Logo successfully converted to base64');
            } else {
                \Log::warning('Invalid image format for logo');
            }
        } else {
            \Log::warning('Failed to fetch image content from: ' . $imageUrl);
            
           
            try {
                $s3Client = Storage::disk('s3');
                if (method_exists($s3Client, 'get')) {
                    $imageContent = $s3Client->get($brandLogo->brand_image);
                    if ($imageContent) {
                        $imageInfo = @getimagesizefromstring($imageContent);
                        if ($imageInfo !== false) {
                            $mimeType = $imageInfo['mime'];
                            $logoData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                            \Log::info('Logo loaded via AWS SDK method');
                        }
                    }
                }
            } catch (Exception $e) {
                \Log::warning('AWS SDK method also failed: ' . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        \Log::error('Failed to load brand logo: ' . $e->getMessage());
    }
}

// Your existing data array remains the same
$data = [
    'property_address' => $pushPropertyAddress,
    'receiptInformation' => $receiptInformation,
    'footerDetails' => $footerDetails,
    'receiptDetails' => $receiptDetails,
    'receiptData' => $receiptData,
    'brandStatement' => $brandStatement,
    'brandLogo' => $brandLogo,
    'logoData' => $logoData,
    'company' => $company,
    'user' => $user,
];
    // Generate PDF
    $pdf = PDF::loadView('accounts::transactionReceiptPdf', $data);
    
    // Create unique filename with timestamp
    $timestamp = date('YmdHis');
    $filename = "receipt_{$receipt_id}_{$timestamp}.pdf";
    $path = config('app.asset_s') . '/Document/' . $filename;
    
    // Store PDF to S3
    Storage::disk('s3')->put($path, $pdf->output());
    
    // Update receipt record
    $receipt->totalTaxAmount = $totalTaxAmount;
    $receipt->doc_path = $path;
    $receipt->save();

    // Return file details
    return [
        'file_path' => $path,
        'file_name' => $filename,
        'file_type' => '.pdf',
        'success' => true
    ];
}
}
