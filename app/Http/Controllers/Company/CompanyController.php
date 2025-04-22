<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounts\Entities\Account;
use Modules\Accounts\Entities\FolioLedger;
use Modules\Accounts\Entities\FolioLedgerBalance;
use Modules\Contacts\Entities\ContactPhysicalAddress;
use Modules\Contacts\Entities\ContactPostalAddress;
use Modules\Contacts\Entities\Contacts;
use Modules\Contacts\Entities\SupplierContact;
use Modules\Contacts\Entities\SupplierDetails;
use Modules\Contacts\Entities\SupplierPayments;
use Modules\Settings\Entities\CompanySetting;
use Modules\Settings\Entities\Country;
use Modules\Settings\Entities\MessagePortfolioEmailSetting;
use Modules\Settings\Entities\MessageSetting;
use Modules\Settings\Entities\Region;
use Modules\Messages\Entities\MessageActionName;
use Modules\Messages\Entities\MessageActionTriggerPoint;
use Modules\Messages\Entities\MessageActionTriggerTo;

class CompanyController extends Controller
{
    public $accounts = [
        [
            "name" => "Administration fee",
            "code" => 515,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Advertising",
            "code" => 540,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Air conditioning",
            "code" => 505,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Appliances",
            "code" => 480,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Blinds and curtains",
            "code" => 490,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Bond",
            "code" => 205,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Bond Claim - General Repairs Maintenance",
            "code" => 300,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Bond refund",
            "code" => 235,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Cleaning",
            "code" => 500,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Council rates",
            "code" => 400,
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Dishonour Fee",
            "code" => 245,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Dishonour Fee",
            "code" => 411,
            "type" => "Income",
            "des" => "Dishonour Fee",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Electrical",
            "code" => 450,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Fire protection",
            "code" => 485,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Flooring and carpets",
            "code" => 455,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Gardening",
            "code" => 465,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "General Maintenance",
            "code" => 615,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Inspection fee",
            "code" => 525,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Insurance Claim",
            "code" => 310,
            "type" => "Income",
            "des" => "Insurance Claim",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Land tax",
            "code" => 415,
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Landlord insurance",
            "code" => 425,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Landlord Insurance Claim",
            "code" => 320,
            "type" => "Income",
            "des" => "Landlord Insurance Claim",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Lease renewal",
            "code" => 530,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Lease Transfer Fee",
            "code" => 444,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Leasing fee",
            "code" => 535,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Legal Fee",
            "code" => 451,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Letting fee",
            "code" => 520,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "List Deposit",
            "code" => 220,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Locks and keys",
            "code" => 435,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Maintenance",
            "code" => 430,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Management fee",
            "code" => 510,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Money received from Owners",
            "code" => 612,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Outgoing Recover",
            "code" => 456,
            "type" => "Income",
            "des" => "Outgoing Recover",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Owner Compensation - General Maintenance",
            "code" => 700,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Owner Contribution",
            "code" => 251,
            "type" => "Income",
            "des" => "Owner Contribution",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Owner Reimbursement - General Maintenance",
            "code" => 713,
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],
        [
            "name" => "Painting",
            "code" => 440,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Pest control",
            "code" => 470,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Plumbing",
            "code" => 445,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Pool",
            "code" => 495,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Refund Sales Deposit",
            "code" => 555,
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Rent",
            "code" => 200,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Rent (with tax)",
            "code" => 230,
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Sale Deposit",
            "code" => 225,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],

        [
            "name" => "Sales Advertising",
            "code" => 550,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],

        [
            "name" => "Sales Commission",
            "code" => 545,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Security",
            "code" => 475,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Strata rates",
            "code" => 410,
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Sundry Fee",
            "code" => 438,
            "type" => "Expense",
            "des" => "Sundry Fee",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Tenant contribution",
            "code" => 240,
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],
        [
            "name" => "Tenant Reimbursement - Electrical",
            "code" => "380",
            "type" => "Income",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Tenant Reimbursement - Rent",
            "code" => "710",
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],
        [
            "name" => "Tenant Reimbursement - Telephone Connection Fee",
            "code" => "711",
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Tribunal fees",
            "code" => "420",
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Utilities",
            "code" => "611",
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ],
        [
            "name" => "Water rates",
            "code" => "405",
            "type" => "Expense",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],
        [
            "name" => "Water usage",
            "code" => "210",
            "type" => "Income",
            "des" => "",
            "tax" => false,
            "hidden" => false,
        ],
        [
            "name" => "Windows and doors",
            "code" => "460",
            "type" => "Expense",
            "des" => "",
            "tax" => true,
            "hidden" => false,
        ]
    ];

    public $names = [
        "Contact",
        "Inspections All",
        "Inspections Routine",
        "Job",
        "Key Management",
        "Lease Renewal",
        "Messages",
        "Owner Contact",
        "Reminders - Property",
        "Rental Listing",
        "Sale Listing",
        "Sales Agreement",
        "Task",
        "Tenancy",
        "Tenant Invoice",
        "Tenant Receipt",
        "Tenant Rent Invoice",
        "Tenant Statement",
        "Folio Receipt",
        "Owner Financial Activity",
        "Owner Statement",
        "Supplier Statement"
    ];

    public $defaultTriggerPoints = [
        'Contact' => ['Manual'],
        'Inspections All' => [
            'Manual',
            'Scheduled',
            'Rescheduled',
            'Shared with owner',
            'Shared with tenant',
            'Assigned to tenant',
            'Returned by tenant',
            'Closed'
        ],
        'Inspections Routine' => [
            'Manual',
            'Scheduled',
            'Rescheduled',
            'Shared with owner',
            'Shared with tenant',
            'Closed'
        ],
        'Job' => [
            'Manual',
            'Pending',
            'Reported',
            'Rejected',
            'Quoted',
            'Assigned',
            'Finished',
            'Completed',
            'Approved',
            'Unapprove',
            'Unquoted'
        ],
        'Key Management' => ['Manual'],
        'Lease Renewal' => ['Manual'],
        'Owner Contact' => ['Manual'],
        'Reminders - Property' => [
            'Manual',
            'Report'
        ],
        'Rental Listing' => [
            'Manual',
            'Created',
            'Published',
            'Leased',
            'Closed'
        ],
        'Sale Listing' => [
            'Manual',
            'Created',
            'Published',
            'Leased',
            'Closed'
        ],
        'Sales Agreement' => [
            'Manual',
            'Contracted',
            'Listed'
        ],
        'Task' => [
            'Created',
            'Manual'
        ],
        'Tenancy' => [
            'Manual',
            'Created',
            'Rent Adjustment'
        ],
        'Tenant Invoice' => [
            'Manual',
            'Created'
        ],
        'Tenant Receipt' => ['Receipted'],
        'Tenant Rent Invoice' => [
            'Manual',
            'Created'
        ],
        'Tenant Statement' => ['Disbursed'],
        'Folio Receipt' => ['Receipted'],
        'Owner Financial Activity' => ['Created'],
        'Owner Statement' => ['Disbursed'],
        'Supplier Statement' => ['Disbursed']
    ];

    public $defaultTriggerTos = [
        'Contact' => ['Contact'],
        'Inspections All' => [
            'Owner',
            'Tenant'
        ],
        'Inspections Routine' => [
            'Owner',
            'Tenant'
        ],
        'Job' => [
            'Supplier',
            'Owner',
            'Tenant',
            'Agent',
        ],
        'Key Management' => ['Checked Out To'],
        'Lease Renewal' => [
            'Owner',
            'Tenant'
        ],
        'Owner Contact' => ['Owner'],
        'Reminders - Property' => [
            'Owner',
            'Tenant',
            'Supplier'
        ],
        'Rental Listing' => [
            'Tenant',
            'Owner'
        ],
        'Sale Listing' => [
            'Tenant',
            'Owner'
        ],
        'Sales Agreement' => [
            'Buyer',
            'Seller'
        ],
        'Task' => [
            'Contact',
            'Owner',
            'Tenant'
        ],
        'Tenancy' => [
            'Owner',
            'Tenant',
            'Strata Manager'
        ],
        'Tenant Invoice' => ['Tenant'],
        'Tenant Receipt' => ['Tenant'],
        'Tenant Rent Invoice' => ['Tenant'],
        'Tenant Statement' => ['Tenant'],
        'Folio Receipt' => ['Folio'],
        'Owner Financial Activity' => ['Owner'],
        'Owner Statement' => ['Owner'],
        'Supplier Statement' => ['Supplier']
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $companies = Company::all();

        $data = [
            'companies' => $companies
        ];
        return response()->json(['data' => $data, 'message' => 'Successfull']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $attributeNames = array(
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'address' => $request->address,
        );

        $validator = Validator::make($attributeNames, [
            'company_name' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
        } else {
            $db = DB::transaction(function () use ($request) {
                $company = new Company();
                $company->company_name = $request->company_name;
                $company->address = $request->address;
                // $company->country = $request->country;
                $company->phone = $request->phone;
                $company->slug = Str::of($request->company_name)->slug('-');
                $company->save();

                foreach ($this->accounts as $value) {
                    $chartofaccount = new Account();
                    $chartofaccount->account_name = $value["name"];
                    $chartofaccount->company_id = $company->id;
                    $chartofaccount->type = $value["type"];
                    $chartofaccount->description = $value["des"];
                    $chartofaccount->account_number = $value["code"];
                    $chartofaccount->tax = $value["tax"];
                    $chartofaccount->hidden = $value["hidden"];
                    $chartofaccount->save();
                }

                foreach ($this->names as $name) {
                    $action = new MessageActionName();
                    $action->name = $name;
                    $action->company_id = $company->id;
                    $action->save();

                    if (isset($this->defaultTriggerPoints[$name])) {
                        foreach ($this->defaultTriggerPoints[$name] as $triggerPoint) {
                            $triggerPointModel = new MessageActionTriggerPoint();
                            $triggerPointModel->action_id = $action->id;
                            $triggerPointModel->trigger_point = $triggerPoint;
                            $triggerPointModel->company_id = $company->id;
                            $triggerPointModel->save();
                        }
                    }

                    if (isset($this->defaultTriggerTos[$name])) {
                        foreach ($this->defaultTriggerTos[$name] as $triggerTo) {
                            $triggerToModel = new MessageActionTriggerTo();
                            $triggerToModel->action_id = $action->id;
                            $triggerToModel->trigger_to = $triggerTo;
                            $triggerToModel->company_id = $company->id;
                            $triggerToModel->save();
                        }
                    }
                }

                $contacts = new Contacts();
                $contacts->reference = $request->company_name;
                $contacts->type = $request->type;
                $contacts->first_name = $request->company_name;
                $contacts->last_name = "Properties";
                $contacts->salutation = NULL;
                $contacts->company_name = $request->company_name;
                $contacts->mobile_phone = $request->phone;
                $contacts->work_phone = $request->phone;
                $contacts->email = $request->company_name . $company->id . "@gmail.com";
                $contacts->abn = $request->abn != null ? $request->abn : '0';
                $contacts->notes = $request->notes;
                $contacts->owner = 0;
                $contacts->tenant = 0;
                $contacts->supplier = 1;
                $contacts->seller = 0;
                $contacts->company_id = $company->id;
                $contacts->save();
                $contactId = $contacts->id;


                $contactPhysicalAddress = new ContactPhysicalAddress();
                $contactPhysicalAddress->contact_id = $contacts->id;
                $contactPhysicalAddress->building_name = $request->physical_building_name;
                $contactPhysicalAddress->unit = $request->physical_unit;
                $contactPhysicalAddress->number = $request->physical_number;
                $contactPhysicalAddress->street = $request->physical_street;
                $contactPhysicalAddress->suburb = $request->physical_suburb;
                $contactPhysicalAddress->postcode = $request->physical_postcode;
                $contactPhysicalAddress->state = $request->physical_state;
                $contactPhysicalAddress->country = $request->physical_country;
                $contactPhysicalAddress->save();

                $contactPostalAddress = new ContactPostalAddress();
                $contactPostalAddress->contact_id = $contacts->id;
                $contactPostalAddress->building_name = $request->postal_building_name;
                $contactPostalAddress->unit = $request->postal_unit;
                $contactPostalAddress->number = $request->postal_number;
                $contactPostalAddress->street = $request->postal_street;
                $contactPostalAddress->suburb = $request->postal_suburb;
                $contactPostalAddress->postcode = $request->postal_postcode;
                $contactPostalAddress->state = $request->postal_state;
                $contactPostalAddress->country = $request->postal_country;
                $contactPostalAddress->save();

                $supplierContact = new SupplierContact();
                $supplierContact->contact_id = $contacts->id;
                $supplierContact->reference = $request->company_name;
                $supplierContact->first_name = $request->company_name;
                $supplierContact->last_name = "Properties";
                $supplierContact->salutation = NULL;
                $supplierContact->company_name = $request->company_name;
                $supplierContact->mobile_phone = $request->phone;
                $supplierContact->work_phone = $request->phone;
                $supplierContact->home_phone = $request->phone;
                $supplierContact->email = $request->company_name . $company->id . "@gmail.com";
                $supplierContact->notes = $request->notes;
                $supplierContact->company_id = $company->id;
                $supplierContact->save();
                $supplierId = $supplierContact->id;

                $supplierDetails = new SupplierDetails();
                $supplierDetails->supplier_contact_id = $supplierContact->id;
                $supplierDetails->abn = $request->abn;
                $supplierDetails->system_folio = true;
                $supplierDetails->website = $request->website;
                $supplierDetails->account = $request->account;
                $supplierDetails->priority = $request->priority;
                $supplierDetails->auto_approve_bills = false;
                $supplierDetails->folio_code = 'SUP000-' . $supplierContact->id;
                $supplierDetails->company_id = $company->id;
                $supplierDetails->save();


                $payment = new SupplierPayments();
                $payment->supplier_contact_id = $supplierId;
                $payment->payment_method = 'EFT';
                $payment->bsb = 12345678;
                $payment->account_no = 12341251;
                $payment->split = 100;
                $payment->split_type = "%";
                $payment->payee = $request->company_name;
                $payment->save();

                $storeLedger = new FolioLedger();
                $storeLedger->company_id = $company->id;
                $storeLedger->date = Date('Y-m-d');
                $storeLedger->folio_id = $supplierDetails->id;
                $storeLedger->folio_type = 'Supplier';
                $storeLedger->opening_balance = 0;
                $storeLedger->closing_balance = 0;
                $storeLedger->save();
                $storeLedgerBalance = new FolioLedgerBalance();
                $storeLedgerBalance->company_id = $company->id;
                $storeLedgerBalance->date = Date('Y-m-d');
                $storeLedgerBalance->folio_id = $supplierDetails->id;
                $storeLedgerBalance->folio_type = 'Supplier';
                $storeLedgerBalance->opening_balance = 0;
                $storeLedgerBalance->closing_balance = 0;
                $storeLedgerBalance->ledger_id = $storeLedger->id;
                $storeLedgerBalance->save();

                // $companySettingAddress = new Country();
                // $companySettingAddress->country_name  = $request->country;
                // $companySettingAddress->save();
                $companySettingAddress = Country::firstOrCreate([
                    'country_name' => $request->country
                ]);
                $companySettingRegion = Region::firstOrCreate([
                    'region_name' => $request->region
                ]);

                // return $companySettingRegion->id;

                $companySetting = new CompanySetting();
                $companySetting->portfolio_supplier = $request->company_name;
                $companySetting->country_id = $companySettingAddress->id;
                $companySetting->region_id = $companySettingRegion->id;
                $companySetting->portfolio_name = $request->company_name;
                $companySetting->licence_number = $request->licence_number;
                $companySetting->include_property_key_number = $request->include_property_key_number == 'on' ? 1 : 0;
                $companySetting->update_inspection_date = $request->update_inspection_date == 'on' ? 1 : 0;
                $companySetting->client_access = $request->client_access == 'on' ? 1 : 0;
                $companySetting->client_access_url = $request->client_access_url == 'on' ? 1 : 0;
                $companySetting->portfolio_id = $request->company_name;
                ;
                $companySetting->working_hours = $request->working_hours;
                // $companySetting->status = $request->status == 'on' ? 1 : 0;
                $companySetting->inspection_report_disclaimer = $request->inspection_report_disclaimer;
                $companySetting->rental_position_on_receipts = $request->rental_position_on_receipts == 'on' ? 1 : 0;
                ;
                $companySetting->show_effective_paid_to_dates = $request->show_effective_paid_to_dates == 'on' ? 1 : 0;
                ;
                $companySetting->include_paid_bills = $request->include_paid_bills == 'on' ? 1 : 0;
                ;
                $companySetting->bill_approval = $request->bill_approval == 'on' ? 1 : 0;
                ;
                $companySetting->join_the_test_program = $request->join_the_test_program == 'on' ? 1 : 0;
                ;
                $companySetting->company_id = $company->id;
                $companySetting->save();

                $messageSetting = new MessageSetting();
                $messageSetting->email_will_be_sent_as = $request->company_name;
                $messageSetting->company_id = $company->id;
                $messageSetting->save();

                $protfolioMessageSetting = new MessagePortfolioEmailSetting();
                $protfolioMessageSetting->portfolio_email = $request->company_name;
                $protfolioMessageSetting->company_id = $company->id;
                $protfolioMessageSetting->message_setting_id = $messageSetting->id;
                $protfolioMessageSetting->save();



                return response()->json(['data' => $contactId, 'message' => 'successful']);
            });
            return $db;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $companies = Company::find($id);
        return response()->json(['companies' => $companies, 'message' => 'Successfull']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = Company::where('id', $id)->first();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $attributeNames = array(
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            // 'created_by'        => $userName,
            // 'soft_delete'       => $defaultValue,
            'address' => $request->address,
            // 'sort_order'        => $request->sort_order
        );

        $validator = Validator::make($attributeNames, [
            'company_name' => 'required',
            'phone' => 'required',
            'address' => 'required',

            // 'created_by'        => 'required',
            // 'soft_delete'       => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()), 422);
        } else {
            $company = Company::find($id);
            $company->update([
                'company_name' => $request->company_name,
                'address' => $request->address,
                'phone' => $request->phone,
                'slug' => Str::of($request->company_name)->slug('-'),

            ]);
            return response()->json(['data' => null, 'message' => 'successful']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->company;
        Company::findOrFail($id)->delete();
        return response()->json(["success"]);
    }
}
