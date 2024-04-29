<?php

namespace App\Models\Imap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Imap\ImapModel;

class ThreadImapModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'thread_imap_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message_id',
        'message_uid',
        'message_no',
        'imap_model_id',
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
     * Get the post that owns the comment.
     */
    public function imap_model()
    {
        return $this->belongsTo(ImapModel::class, 'imap_model_id', 'id');
    }
}