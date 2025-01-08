<?php

namespace Modules\Messages\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MergeSubfield extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'merge_field_id'];

    public function mergeField()
    {
        return $this->belongsTo(MergeField::class);
    }

    protected static function newFactory()
    {
        return \Modules\Messages\Database\factories\MergeSubfieldFactory::new();
    }
}
