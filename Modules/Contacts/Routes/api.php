<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    // return $request->user();


    Route::resource('/contacts', ContactsController::class);

    //ssr//
    Route::get('/contacts-ssr', [Modules\Contacts\Http\Controllers\ContactsController::class, 'index_ssr']);

    Route::post('/contact/email/check', [Modules\Contacts\Http\Controllers\ContactsController::class, 'contactEmailCheck']);
    // Route::post('/owner/email/check', [Modules\Contacts\Http\Controllers\OwnerController::class, 'ownerEmailCheck']);
    // Route::post('/tenant/email/check', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenantEmailCheck']);
    // Route::post('/supplier/email/check', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplierEmailCheck']);
    // Route::post('/seller/email/check', [Modules\Contacts\Http\Controllers\SellerController::class, 'sellerEmailCheck']);
    // Route::post('/buyer/email/check', [Modules\Contacts\Http\Controllers\BuyerController::class, 'buyerEmailCheck']);

    Route::get('/contactType/{type}', [Modules\Contacts\Http\Controllers\ContactsController::class, 'contactType']);
    Route::get('/contactType-ssr/{type}', [Modules\Contacts\Http\Controllers\ContactsController::class, 'contactType_ssr']);

    Route::post('/contactRole', [Modules\Contacts\Http\Controllers\ContactsController::class, 'contactRole']);
    Route::post('/addsupplier', [Modules\Contacts\Http\Controllers\ContactsController::class, 'storeCompanySupplier']);

    // ARCHIVE CONTACT ROUTES
    Route::resource('/contacts-archive', ContactArchiveController::class);
    Route::get('/archive-contact/{id}', [Modules\Contacts\Http\Controllers\ContactArchiveController::class, 'archiveContact']);
    Route::get('/restore-contact/{id}', [Modules\Contacts\Http\Controllers\ContactArchiveController::class, 'restoreContact']);

    //Property Owners

    Route::resource('/owners', OwnerController::class);
    Route::post('/property/owner/store', [Modules\Contacts\Http\Controllers\OwnerController::class, 'owner_contact_store']);
    Route::get('/property/owner/info/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_info']);
    Route::get('/property/owner/info/witharchive/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_info_with_archive']);
    Route::get('/restore/owner/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'restoreOwner']);
    Route::get('/property/all/owner/info/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_all_owner_info']);
    Route::get('/get_ownerFolio/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'get_ownerFolio']);
    Route::get('/owner/folio/edit/{id}/{folioId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'OwnerFolioEdit']);



    Route::put('/property/owner/contact/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'owner_contact_update']);

    Route::get('owner/fee/list', [Modules\Contacts\Http\Controllers\OwnerController::class, 'feeList']);
    Route::get('check/owner/payable/{property_id}/{contactId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'checkOwnerPayable']);
    Route::post('change/owner', [Modules\Contacts\Http\Controllers\OwnerController::class, 'changeOwner']);
    Route::get('get/owner/contact/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'getOwnerContact']);
    Route::get('get/owner/folio/fees/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'getOwnerFolioFees']);
    Route::get('get/owner/property/feelist/{id}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'getOwnerPropertyFees']);

    //Property Tenant
    Route::resource('/tenants', TenantController::class);
    Route::post('property/tenant/store', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenant_contact_store']);
    Route::post('/property/tenant/folio', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenant_folio_store']);
    Route::put('/property/tenant/contact/{id}', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenant_contact_update']);
    Route::get('/property/tenant/info/{propertyId}', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_info']);
    Route::get('/tenant/info/{tenantId}', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenant_info']);
    Route::post('/property/tenant/status/{id}', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_leave']);

    Route::post('/disburse/tenant', [Modules\Contacts\Http\Controllers\TenantController::class, 'disburseTenant']);
    Route::get('/property/tenant/due', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_due']);
    Route::post('/property-tenant-due-check-and-archive', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_due_check_and_archive']);
    Route::get('/restore-tenant/{id}', [Modules\Contacts\Http\Controllers\TenantController::class, 'restore_tenant']);
    Route::post('/property-tenant-due-check', [Modules\Contacts\Http\Controllers\TenantController::class, 'property_tenant_due_check']);
    Route::get('/property-tenants/{id}', [Modules\Contacts\Http\Controllers\TenantController::class, 'propertyTenant']);
    Route::get('/make-tenant/{propId}/{fId}', [Modules\Contacts\Http\Controllers\TenantController::class, 'makeTenant']);

    //Property Supplier
    Route::resource('/supplier', SupplierController::class);
    Route::post('/property/supplier/store', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplier_contact_store']);
    Route::post('/property/supplier/details', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplier_details_store']);
    Route::post('/property/supplier/payment', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplier_payment_store']);
    Route::put('/property/supplier/contact/{id}', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplier_contact_update']);
    Route::get('/supplier/contact/list', [Modules\Contacts\Http\Controllers\SupplierController::class, 'supplier_contact_list']);


    Route::get('/sellAgreement/{id}', [Modules\Contacts\Http\Controllers\SellerController::class, 'salesAgreement']);
    Route::get('/salesAgreementInfo/{id}/{sellerId}', [Modules\Contacts\Http\Controllers\SellerController::class, 'salesAgreementInfo']);
    Route::get('/salesInfo/{id}', [Modules\Contacts\Http\Controllers\SellerController::class, 'salesInfo']);
    Route::get('/salesInfoWithArchive/{id}', [Modules\Contacts\Http\Controllers\SellerController::class, 'salesInfoWithArchive']);
    Route::get('/restore/seller/{id}', [Modules\Contacts\Http\Controllers\SellerController::class, 'restoreSeller']);

    // contact Labels
    Route::resource('contact/info/label', ContactLabelController::class);
    Route::post('/contact/info/label/update', [Modules\Contacts\Http\Controllers\ContactLabelController::class, 'updateLabels']);
    Route::get('/getContactDoc/{id}', [Modules\Contacts\Http\Controllers\ContactsController::class, 'getContactDoc']);

    //property seller
    Route::resource('/sellers', SellerController::class);
    Route::get('/seller/folio', [Modules\Contacts\Http\Controllers\SellerController::class, 'sellerFolios']);
    Route::get('/seller/folio/{id}', [Modules\Contacts\Http\Controllers\SellerController::class, 'sellerFolioShow']);
    Route::post('/property-seller-due-check-and-archive', [Modules\Contacts\Http\Controllers\SellerController::class, 'property_seller_due_check_and_archive']);

    //property buyer
    Route::resource('/buyers', BuyerController::class);


    //message activity
    Route::get('/contacts/message/mail/template/all', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'messagesMailTemplateShow']);
    Route::post('contacts/message/mail/template/activity', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'TemplateActivityStore']);
    Route::post('contacts/message/mail/template/activity/With/Property/id', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'TemplateActivityStoreWithPropertyId']);
    Route::post('contacts/message/mail/template/filter', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'messagesMailTemplatefilter']);
    Route::get('contacts/message/mail/template/search', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'search']);

    //all contact message activity
    Route::post('multiple/contacts/message/mail/template/activity', [Modules\Contacts\Http\Controllers\MessageActivityController::class, 'MultipleContactTemplateActivityStore']);

    /**
     * New separate route for the specified Tenant.
     * @param  tenantContactstore
     * @param tenant Folio
     * @return tenant payment
     */

    // Route::post('property/tenant/contact/store', [Modules\Contacts\Http\Controllers\TenantController::class, 'tenant_contact_store']);


    //owner panal
    Route::get('/property/owner/panel/info/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_panel_info']);
    Route::get('/property/owner/panel/job/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_panel_job']);

    Route::post('/property-owner-due-check-and-archive', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_due_check_and_archive']);


    Route::post('/periodic-tenancy', [Modules\Contacts\Http\Controllers\TenantController::class, 'update_periodic']);
    Route::resource('/rentdetails', RentDetailController::class);

    Route::get('/rentdetails/{id}/{property_id}', [Modules\Contacts\Http\Controllers\RentDetailController::class, 'show']);
    Route::get('/fromRentDetailsUpdateOnTenantFolioRent', [Modules\Contacts\Http\Controllers\RentDetailController::class, 'fromRentDetailsUpdateOnTenantFolioRent']);

    Route::get('/property/owner/panel/Mony-in-out/{propertyId}', [Modules\Contacts\Http\Controllers\OwnerController::class, 'property_owner_panel_money_in_out']);
    //sms template activity
    Route::post('contacts/sms/mail/template/activity', [Modules\Contacts\Http\Controllers\SmsActivityController::class, 'store']);

    Route::resource('/rent-discount', RentDiscountController::class);

    Route::get('/rent-management/{id}/{prop_Id}', [Modules\Contacts\Http\Controllers\RentManagementController::class, 'custom_index_ssr']);

    // SUPPLIER FOLIO
    Route::get('/supplier-folio-info/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_folio_info']);
    Route::get('/supplier-folio-info-with-archive/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_folio_info_with_archive']);
    Route::get('/supplier-transaction-list', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'current_list_by_month']);
    Route::get('/supplier-pending-bill/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_pending_bill']);
    Route::get('/supplier-paid-bill/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_paid_bill']);
    Route::get('/supplier-pending-invoice/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_pending_invoice']);
    Route::get('/supplier-disbursement/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_disbursement']);
    Route::get('/supplier-due-check-and-archive/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'supplier_due_check_and_archive']);
    Route::get('/restore/supplier/{id}', [Modules\Contacts\Http\Controllers\Supplier\SupplierFolioController::class, 'restoreSupplier']);
});
