<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationHistory extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'quotation_id',
        'action',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }
}
