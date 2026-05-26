<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrderBillingFile extends Model
{
    protected $fillable = [
        'job_order_billing_id',
        'file_path',
        'file_name',
    ];

    public function jobOrderBilling()
    {
        return $this->belongsTo(JobOrderBilling::class, 'job_order_billing_id');
    }
}
