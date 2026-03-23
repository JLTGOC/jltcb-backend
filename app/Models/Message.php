<?php 

namespace App\Models;

use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model implements Searchable
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'content', 'type', 'file_name',
        'attachment_path', 'reference_id', 'reference_type'
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
        );
    }

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

    protected $casts = [
        'conversation_id' => 'integer',
        'sender_id' => 'integer',
        'reference_id' => 'integer',
    ];
}