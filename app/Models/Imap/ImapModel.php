<?php

namespace App\Models\Imap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Imap\ThreadImapModel;

class ImapModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'imap_models';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'message_uid',
        'message_no',
        'in_reply_to_id',
        'reply_to_id',
        'subject',
        'from',
        'to',
        'cc',
        'bcc',
        'date',
        'body',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
    ];


    /**
     * Get the thread_imap_models for the imap_model.
     */
    public function thread_imap_models()
    {
        return $this->hasMany(ThreadImapModel::class, 'imap_model_id', 'id');
    }
}