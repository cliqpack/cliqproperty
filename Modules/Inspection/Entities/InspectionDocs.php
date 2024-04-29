<?php

namespace Modules\Inspection\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionDocs extends Model
{
    use HasFactory;

    protected $table = "inspection_docs";

    protected $fillable = [
           'inspection_id',
           'doc_path',
    ];
    
    protected static function newFactory()
    {
        return \Modules\Inspection\Database\factories\InspectionDocsFactory::new();
    }
}
