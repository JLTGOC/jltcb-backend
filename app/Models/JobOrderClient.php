<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrderClient extends Model
{
    protected $fillable = [
        'job_order_id',
        'client_type',
        'accredited',
        'service_type_id',
        'tone_and_attitude',
        'client_remarks',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceOption::class, 'service_type_id');
    }
}
