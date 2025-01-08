<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MergeField extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'message_action_id'];

    public function messageAction()
    {
        return $this->belongsTo(MessageAction::class);
    }

    public function mergeSubfields()
    {
        return $this->hasMany(MergeSubfield::class);
    }

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MergeFieldFactory::new();
    }
}
