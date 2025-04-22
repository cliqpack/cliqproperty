<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FolioLedgerBalance extends Model
{
    use HasFactory;

  
    protected $fillable = ['company_id', 'date', 'folio_id', 'folio_type', 'opening_balance', 'closing_balance', 'updated', 'debit', 'credit', 'ledger_id'];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\FolioLedgerBalanceFactory::new();
    }
}
