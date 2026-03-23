<?php

namespace App\Models;

use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model implements Searchable
{
    protected $table = 'participants';
    public $timestamps = false;

    protected $fillable = ['conversation_id', 'user_id', 'role', 'last_read_at', 'joined_at'];

    protected $casts = [
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
        'conversation_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
            null
        );
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
