<?php

namespace Modules\Accounts\Http\Controllers\Withdrawal;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounts\Entities\Withdrawal;

class WithdrawalStoreController extends Controller
{
    public $receipt_id;
    public $disbursement_id;
    public function __construct($receipt_id, $disbursement_id)
    {
        $this->receipt_id = $receipt_id;
        $this->disbursement_id = $disbursement_id;
    }
    public function withdrawal_store(array $withdrawal)
    {
        $withdraw = new Withdrawal();
        $withdraw->property_id = !empty($withdrawal['property_id']) ? $withdrawal['property_id'] : NULL;
        $withdraw->receipt_id = $this->receipt_id;
        $withdraw->disbursement_id = $this->disbursement_id;
        $withdraw->create_date = !empty($withdrawal['create_date']) ? $withdrawal['create_date'] : NULL;
        $withdraw->contact_payment_id = !empty($withdrawal['contact_payment_id']) ? $withdrawal['contact_payment_id'] : NULL;
        $withdraw->contact_type = !empty($withdrawal['contact_type']) ? $withdrawal['contact_type'] : NULL;
        $withdraw->amount = !empty($withdrawal['amount']) ? $withdrawal['amount'] : NULL;
        $withdraw->customer_reference = !empty($withdrawal['customer_reference']) ? $withdrawal['customer_reference'] : NULL;
        $withdraw->statement = !empty($withdrawal['statement']) ? $withdrawal['statement'] : NULL;
        $withdraw->payment_type = !empty($withdrawal['payment_type']) ? $withdrawal['payment_type'] : NULL;
        $withdraw->complete_date = !empty($withdrawal['complete_date']) ? $withdrawal['complete_date'] : NULL;
        $withdraw->cheque_number = !empty($withdrawal['cheque_number']) ? $withdrawal['cheque_number'] : NULL;
        $withdraw->total_withdrawals = !empty($withdrawal['total_withdrawals']) ? $withdrawal['total_withdrawals'] : NULL;
        $withdraw->company_id = !empty($withdrawal['company_id']) ? $withdrawal['company_id'] : NULL;
        $withdraw->save();
    }
}
