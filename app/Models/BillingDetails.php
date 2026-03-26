<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingDetails extends Model
{
    protected $fillable = [
        'job_order_id',
        'hs_code',
        'permits',
        'special_remarks',
        'terms_of_payment',
        'billing_date',
        'shall_be_billed',
        'closing_remarks'
    ];

    protected $casts = [
        'job_order_id' => 'integer',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }
}
