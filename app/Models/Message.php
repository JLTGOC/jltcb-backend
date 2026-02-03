<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'content', 'type', 
        'attachment_path', 'reference_id', 'reference_type'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // This links to Quotation or Transaction
    public function reference()
    {
        return $this->morphTo();
    }
}