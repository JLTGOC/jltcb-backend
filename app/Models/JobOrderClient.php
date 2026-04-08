<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrderClient extends Model
{
    protected $fillable = [
        'job_order_id',
        'client_type',
        'accredited',
        'client_remarks',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }
}
