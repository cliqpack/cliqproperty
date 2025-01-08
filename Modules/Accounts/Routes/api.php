<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// use Modules\Accounts\Http\Controllers\BillsController;

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
    // Account
    Route::post('/account/store', [Modules\Accounts\Http\Controllers\AccountsController::class, 'account_store']);
    Route::post('/receipt/reverse/{id}', [Modules\Accounts\Http\Controllers\AccountsController::class, 'receiptReverse']);
    Route::post('/tenant/receipt/reverse/{id}', [Modules\Accounts\Http\Controllers\AccountsController::class, 'tenantReceiptReverse']);
    Route::get('/account/bill', [Modules\Accounts\Http\Controllers\AccountsController::class, 'bill']);
    Route::get('/inv-acc-list', [Modules\Accounts\Http\Controllers\AccountsController::class, 'invoice']);
    Route::get('/bill-acc-list', [Modules\Accounts\Http\Controllers\AccountsController::class, 'billAccounts']);
    Route::get('/accounts', [Modules\Accounts\Http\Controllers\AccountsController::class, 'accounts']);
    // Bill
    Route::resource('/bills', BillsController::class);
    Route::resource('/chart-of-account', ChartOfAccountController::class);
    Route::resource('/bill-account', BillAccountsController::class);
    //tenant Recipt Info
    Route::get('/account/tenant-recipt-info/{tenant_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'tenantReciptInfo']);
    Route::get('/pay-bill/{id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'payBill']);
    Route::post('/selected-bill-pay', [Modules\Accounts\Http\Controllers\BillsController::class, 'selectedBillPay']);

    Route::post('/update-bill/{id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'updateBill']);
    Route::post('/delete-multiple-bill', [Modules\Accounts\Http\Controllers\BillsController::class, 'multipleBillDelete']);

    Route::get('/get-future-bill-list', [Modules\Accounts\Http\Controllers\BillsController::class, 'futurePayBillList']);
    Route::get('/get-paid-bill-list', [Modules\Accounts\Http\Controllers\BillsController::class, 'paidBillList']);
    Route::get('/get-uploaded-bill-list', [Modules\Accounts\Http\Controllers\BillsController::class, 'uploadedBillList']);
    Route::get('/get-approval-bill-list', [Modules\Accounts\Http\Controllers\BillsController::class, 'approvalBillList']);
    Route::get('/approve-bill/{id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'approveBill']);
    Route::post('/approve-multiple-bill', [Modules\Accounts\Http\Controllers\BillsController::class, 'approveMultipleBill']);
    Route::get('/get-job-list/{property_id}/{supplier_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'getJobList']);
    Route::get('/get-agreement-renew-fee/{property_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'getAgreementFee']);
    Route::post('/charge/manual/fee', [Modules\Accounts\Http\Controllers\BillsController::class, 'chargeManualFee']);
    Route::get('/generate-bill-pdf', [Modules\Accounts\Http\Controllers\BillsController::class, 'billTrigger']);
    Route::get('/bill/property/list', [Modules\Accounts\Http\Controllers\BillsController::class, 'billPropertyList']);

    // Invoice
    Route::resource('/account-invoice-bill', InvoiceController::class);
    Route::get('/doc-generate', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'docGen']);
    Route::get('/get-future-invoice-list', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'futureInvoiceBillList']);
    Route::get('/get-paid-invoice-list', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'paidInvoiceBillList']);
    Route::get('/get-uploaded-invoice-list', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'uploadedInvoiceBillList']);
    Route::get('/get-property-tenant/{property_id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'getPropertyTenant']);
    Route::post('/update-invoice/{id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'invoiceUpdate']);
    Route::post('/delete-invoices', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'destroyInvoices']);

    Route::get('/account/tenant-folio-list', [Modules\Accounts\Http\Controllers\AccountsController::class, 'tenantFolioList']);
    // Route::get('/account/tenant-folio-list/{id}', [Modules\Accounts\Http\Controllers\AccountsController::class, 'tenantFolioListSingle']);
    //Account
    Route::resource('/account-receipt', AccountsController::class); // receipt add
    Route::get('/tenant-receipt-list', [Modules\Accounts\Http\Controllers\AccountsController::class, 'index_single_tenant']);
    Route::get('/rent-actions/{id}', [Modules\Accounts\Http\Controllers\AccountsController::class, 'rentActionList']);
    Route::post('/rent-action-store', [Modules\Accounts\Http\Controllers\AccountsController::class, 'rentActionStore']);
    Route::post('/rent-action-delete', [Modules\Accounts\Http\Controllers\AccountsController::class, 'deleteRentAction']);
    Route::post('/tenant-deposit-receipt', [Modules\Accounts\Http\Controllers\AccountsController::class, 'tenantDepositReceipt']);

    Route::get('/owner/receipt-list-by-month', [Modules\Accounts\Http\Controllers\AccountsController::class, 'ownerReceiptListByCurrentMonth']);
    Route::get('/owner/folio/all/transactions', [Modules\Accounts\Http\Controllers\AccountsController::class, 'ownerFolioByAllTransaction']);
    Route::post('/edit/account', [Modules\Accounts\Http\Controllers\AccountsController::class, 'editAccountId']);

    Route::get('/seller/receipt-list-by-month', [Modules\Accounts\Http\Controllers\AccountsController::class, 'sellerReceiptListByCurrentMonth']);

    // Receipt
    Route::get('/receipt-list-by-month/{month}/{year}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receiptListByMonth']);
    Route::get('/receipt-list-report-by-month/{month}/{year}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receiptListReportByMonth']);

    Route::get('/get-receipt-folios/{type}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receipt_folios']);
    Route::get('/get-all-receipt-folios/{type}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receipt_folios_ssr']);
    Route::get('/get-receipt-folio-balance/{type}/{id}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receipt_folio_balance']);
    Route::post('/folio-receipt', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'folio_receipt_store']); //receipt add
    Route::post('/folio-withdraw', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'folio_withdraw_store']);
    Route::post('/journal', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'journal']);
    Route::post('/seller-folio-receipt', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'sales_folio_receipt_store']);


    Route::post('/import/bank/file', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'importBankFile']);
    Route::get('/get/bank/import/Reconciliation', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'getBankImportReconciliation']);
    Route::post('/import/bank/reconciliation', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'importBankReconciliation']);
    Route::post('/receipt/reconciliation', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receiptReconciliation']);
    Route::post('/receipt/as/rent', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'receiptAsRent']);
    Route::delete('/import/bank/file/delete/{id}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'importBankFileDelete']);
    Route::get('/owner_money_in_out/{id}', [Modules\Accounts\Http\Controllers\ReceiptController::class, 'owner_money_in_out']);

    // Banking
    Route::get('/last-deposit', [Modules\Accounts\Http\Controllers\BankingController::class, 'getLastDiposit']);
    Route::get('/banking-deposit-list-details', [Modules\Accounts\Http\Controllers\BankingController::class, 'bankDepositListDetails']);
    Route::get('/banking-deposit-list-details-amount', [Modules\Accounts\Http\Controllers\BankingController::class, 'bankDepositListDetailsAmount']);
    Route::get('/monthly-banking-deposit-list/{month}/{year}', [Modules\Accounts\Http\Controllers\BankingController::class, 'monthlyDepositListDetails']);
    Route::post('/current-deposit', [Modules\Accounts\Http\Controllers\BankingController::class, 'depositAlllData']);
    Route::post('/deposit-selected-current-list', [Modules\Accounts\Http\Controllers\BankingController::class, 'depositSelectedData']);
    Route::get('/current-deposit-in-one-list', [Modules\Accounts\Http\Controllers\BankingController::class, 'currentDepositListOneData']);
    Route::get('/current-deposit-list/{id}', [Modules\Accounts\Http\Controllers\BankingController::class, 'currentDepositData']);
    Route::get('/unreconciled-deposit-list/{month}/{year}', [Modules\Accounts\Http\Controllers\BankingController::class, 'unreconciledDepositListByMonth']);
    Route::get('/unreconciled-all-deposit-list/{month}/{year}', [Modules\Accounts\Http\Controllers\BankingController::class, 'allDepositListByMonth']);
    Route::post('/cancelLastDiposit', [Modules\Accounts\Http\Controllers\BankingController::class, 'cancelLastDiposit']);

    // Reconcillation
    Route::get('/reconcillation-list', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcillationList']);
    Route::post('/reconcillation-list-details', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcillationListDetails']);
    Route::get('/unreconcillied/withdrawls/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'unreconcillied_withdrawls']);
    Route::get('/all/reconcillied/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'all_reconcillied']);
    Route::put('/unreconcillied/withdrawls', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'unreconcillied_withdrawls_update']);
    Route::put('/reconcillied', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcillied_withdrawls_update']);


    Route::post('/store-adjustment', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'storeAdjustment']);
    Route::post('/remove-adjustment', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'removeAdjustment']);
    Route::get('/adjustment-list/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'adjustmentList']);
    Route::get('/all-adjustment-list/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'allAdjustmentList']);
    Route::post('/reconcile-deposit', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcileDepositData']);
    Route::post('/unreconcile-deposit', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'unReconcileDepositData']);
    Route::get('/unreconcilled-items/{month}/{year}/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'unreconciledItems']);

    // RECONCILIATION RELATED ROUTE
    Route::get('/reconcilliation_store', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcilliation_store']);
    Route::get('/reconcile/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'reconcilliation']);
    Route::get('/approve/reconciliation/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'approveReconciliation']);
    Route::get('/revoke/reconciliation/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'revokeReconciliation']);
    Route::post('/bankStatementBalance/{id}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'bankStatementBalance']);
    Route::get('/trialBalance/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'trialBalance']);
    Route::get('/journalBalance/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'journalBalance']);
    Route::get('/cashBookBalance/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'cashBookBalance']);
    Route::get('/transactionAudit/{year}/{month}', [Modules\Accounts\Http\Controllers\ReconcillationController::class, 'transactionAudit']);

    Route::get('/reconciliation/approved', [Modules\Accounts\Http\Controllers\ReconciliationReversalController::class, 'approvedReconciliation']);
    // -----------------
    //Disbursement Start
    Route::get('/total-due-disbursement', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'totalDueDisbursement']);
    Route::get('/disbursement/{type}', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'index']);
    Route::get('/newdisbursement/{type}', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'indexSSr']);
    Route::get('/allSupplierDisbursementList', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'allSupplierDisbursementList']);
    Route::get('/disbursement/create', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'create']);
    Route::post('/disburse/complete', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'disburseComplete']);
    Route::post('single/disburse/complete/{ownerFolioId}', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'singleDisburseComplete']);
    Route::post('/disburse/preview', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'disbursementPreview']);
    Route::post('supplier/disburse/complete', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'supplierDisburseComplete']);
    Route::post('seller/disburse/complete/{sellerFolioId}', [Modules\Accounts\Http\Controllers\DisbursementController::class, 'singleDisburseCompleteSeller']);

    //Disbursement End
    //FolioLedgerController Start
    Route::get('/folioledger/{year}/{month}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'index']);
    Route::get('/folioled', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'folioLedgerUpdate']);
    Route::get('/ownerfolioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'ownerFolioLedger']);
    Route::post('/owner/filter/folioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'ownerFilteredFolioLedger']);
    Route::get('/folioledger/next_date_opening_balance', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'next_date_opening_balance']);
    Route::get('/od/summary/transaction/{id}', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFolioSummaryController::class, 'odtransaction']);
    Route::get('/owner/summary/transaction/{id}', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFolioSummaryController::class, 'transaction']);
    Route::get('/summary/transaction/byreport/{id}', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFolioSummaryController::class, 'summaryByReport']);
    Route::get('/summary/transaction/bymonthinfo/{id}', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFolioSummaryController::class, 'summaryByMonthInfo']);
    Route::get('/owner/folio/properties/{id}', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFolioSummaryController::class, 'properties']);
    Route::get('/owner/statements/{id}/{property_id}', [Modules\Accounts\Http\Controllers\OwnerStatements\OwnerStatementsController::class, 'ownerStatements']);
    Route::resource('/ownerfinancialactivity', OwnerFolioSummary\OwnerFinancialActivityController::class);
    Route::post('/ownerfinancialactivity/delete', [Modules\Accounts\Http\Controllers\OwnerFolioSummary\OwnerFinancialActivityController::class, 'destroyMultiple']);

    Route::get('/supplier/summary/transaction/{id}', [Modules\Accounts\Http\Controllers\SupplierFolioSummary\SupplierFolioSummaryController::class, 'supplierTransaction']);
    Route::get('/supplier/summary/transaction/byreport/{id}', [Modules\Accounts\Http\Controllers\SupplierFolioSummary\SupplierFolioSummaryController::class, 'summaryByReport']);
    Route::get('/supplier/summary/transaction/bymonthinfo/{id}', [Modules\Accounts\Http\Controllers\SupplierFolioSummary\SupplierFolioSummaryController::class, 'summaryByMonthInfo']);
    // for supplier 
    Route::get('/supplierfolioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'supplierFolioLedger']);
    Route::post('/supplier/filter/folioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'supplierFilteredFolioLedger']);
    // for seller
    Route::get('/sellerfolioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'sellerFolioLedger']);
    Route::post('/seller/filter/folioledger/{id}', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'sellerFilteredFolioLedger']);

    //FolioLedgerController End

    // Withdrawal Start
    Route::resource('/withdraw', WithdrawController::class);
    Route::get('/withdrawals', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'withdrawals']);
    Route::get('/all-withdrawal', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'allWithdrawal']);
    Route::get('/withdraw/{month}/{year}/{type}', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'withdrawList']);
    Route::get('/all-withdraw/{month}/{year}', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'AllWithdrawList']);
    Route::get('/all-withdraw-report/{month}/{year}', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'AllWithdrawReportList']);
    Route::post('/eft-bpay-withdraw', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'eftBpayWithdraw']);
    Route::post('/cheque-withdraw', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'chequeWithdraw']);

    //tenent unpaid invoice //
    // Route::get('/tenant-unpaid-invoice/{id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'tenant_unpaid_invoice']);
    Route::get('/tenant-pending-invoice/{pro_id}/{tenant_id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'tenant_pending_invoice']);
    Route::get('/tenant-paid-invoice/{pro_id}/{tenant_id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'tenant_paid_invoice']);
    Route::get('/owner-pending-invoice/{pro_id}/{owner_id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'owner_pending_invoice']);
    Route::get('/owner-paid-invoice/{pro_id}/{owner_id}', [Modules\Accounts\Http\Controllers\InvoiceController::class, 'owner_paid_invoice']);
    Route::get('/owner-pending-bill/{pro_id}/{owner_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'owner_pending_bill']);
    Route::get('/owner-paid-bill/{pro_id}/{owner_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'owner_paid_bill']);
    Route::get('/check-recurring', [Modules\Accounts\Http\Controllers\RecurringFeeBillController::class, 'triggerPlan']);

    // PLAN ROUTES
    Route::get('/owner/plan/{folioid}', [Modules\Accounts\Http\Controllers\PlanController::class, 'ownerPlanAddons']);

    Route::get('/seller-pending-bill/{pro_id}/{seller_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'seller_pending_bill']);
    Route::get('/seller-paid-bill/{pro_id}/{seller_id}', [Modules\Accounts\Http\Controllers\BillsController::class, 'seller_paid_bill']);

    // RENT MANAGEMENT
    Route::post('/reset-rent-management', [Modules\Accounts\Http\Controllers\RentManagement\RentManagementController::class, 'resetRentManagement']);
    Route::post('/generate-recurring-invoice', [Modules\Accounts\Http\Controllers\RentManagement\RecurringInvoiceController::class, 'generateRecurringInvoice']);
    Route::post('/cancel-recurring-invoice', [Modules\Accounts\Http\Controllers\RentManagement\RecurringInvoiceController::class, 'cancelRecurringInvoice']);

    // TRIGGER
    Route::get('/trigger/folio_ledger_store', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'folioLedgerUpdate']);
    Route::get('/trigger/company/folio_ledger_store', [Modules\Accounts\Http\Controllers\FolioLedgerController::class, 'companyFolioLedgerUpdate']);

    Route::get('/trigger/recurring_bills', [Modules\Accounts\Http\Controllers\BillsController::class, 'checkRecurring']);
    Route::get('/trigger/company/recurring_bills', [Modules\Accounts\Http\Controllers\BillsController::class, 'checkCompanyRecurring']);

    Route::get('/trigger/recurring_propertyfeebills', [Modules\Accounts\Http\Controllers\RecurringFeeBillController::class, 'recurringPropertyFeeBill']);
    Route::get('/trigger/company/recurring_propertyfeebills', [Modules\Accounts\Http\Controllers\RecurringFeeBillController::class, 'recurringCompanyPropertyFeeBill']);

    Route::get('/trigger/trigger_fees', [Modules\Accounts\Http\Controllers\BillsController::class, 'triggerRecurringFees']);
    Route::get('/trigger/company/trigger_fees', [Modules\Accounts\Http\Controllers\BillsController::class, 'triggerCompanyRecurringFees']);

    Route::get('/trigger/trigger_plan', [Modules\UserACL\Http\Controllers\OwnerPlanController::class, 'triggerPlan']);
    Route::get('/trigger/company/trigger_plan', [Modules\UserACL\Http\Controllers\OwnerPlanController::class, 'triggerCompanyPlan']);

    Route::get('/dummy', [Modules\Accounts\Http\Controllers\DummyController::class, 'dummy']);
    Route::post('/only-bpay-withdraw', [Modules\Accounts\Http\Controllers\WithdrawController::class, 'onlyBpayWithdraw']);
});
Route::get('/generate-pdf', [Modules\Accounts\Http\Controllers\AccountsController::class, 'generatePDF']);
