<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFileChecklistItem extends Model
{
    protected $fillable = [
        'name',
        'visibility',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
