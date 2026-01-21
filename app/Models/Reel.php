<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'video_path',
        'view_count',
    ];

    protected $casts = [
        'view_count' => 'integer',
    ];

    /**
     * Increment the view count for this reel.
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
