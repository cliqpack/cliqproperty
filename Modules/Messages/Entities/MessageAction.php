<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageAction extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function mergeFields()
    {
        return $this->hasMany(MergeField::class);
    }

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MessageActionFactory::new();
    }
}
