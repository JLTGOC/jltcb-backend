<?php

namespace App\Models;

use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Scope;

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

    public function getLastMessageType() {
        $lastMessageType = match ($this->lastMessage->type) {
            'TEXT' => $this->lastMessage->content,
            'IMAGE' => '[Image]',
            'FILE' => '[File]',
            'QUOTATION_CARD' => '[Quotation Card]',
            'SHIPMENT_CARD' => '[Shipment Card]',
            default => 'No message'
        };  

        return $lastMessageType;
    }

    public function getUnreadCountFor($user) {
        
        $lastRead = $this->participants()->where('user_id', $user->id)->first()->pivot->last_read_at;

        // Base query for messages not sent by the user
        $query = $this->messages()
            ->where(function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                ->orWhereNull('sender_id');
            });

        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }

        return $query->count();
    }
}