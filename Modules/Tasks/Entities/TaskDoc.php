<?php

namespace Modules\Tasks\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskDoc extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'doc_path',
 ];
    
    protected static function newFactory()
    {
        return \Modules\Tasks\Database\factories\TaskDocFactory::new();
    }
}
