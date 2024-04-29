<?php

namespace Modules\Accounts\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FolioLedgerDetailsDaily extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Accounts\Database\factories\FolioLedgerDetailsDailyFactory::new();
    }
    public function folioLedger()
    {
        return $this->hasOne(FolioLedger::class, 'id', 'folio_ledgers_id');
    }
}
