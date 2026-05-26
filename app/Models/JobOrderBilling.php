<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrderBilling extends Model
{
    protected $fillable = [
        'job_order_id',
        'terms_of_payment',
        'billing_date',
        'shall_be_billed',
        'listed_docs',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function billingFiles()
    {
        return $this->hasMany(JobOrderBillingFile::class, 'job_order_billing_id');
    }
}
