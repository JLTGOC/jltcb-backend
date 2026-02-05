<?php

namespace App\Models;

use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Conversation extends Model implements Searchable
{
    use HasUuids;

    protected $fillable = ['type', 'name', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
            null
        );
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'participants')
            ->withPivot('role', 'last_read_at', 'joined_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}