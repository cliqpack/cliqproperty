<?php

namespace Modules\Settings\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileFormat extends Model
{
    use HasFactory;

    protected $fillable = ['file_name'];

    protected static function newFactory()
    {
        return \Modules\Settings\Database\factories\FileFormatFactory::new();
    }
}
