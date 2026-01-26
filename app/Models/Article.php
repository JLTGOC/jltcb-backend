<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'image_url',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
