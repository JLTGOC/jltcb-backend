<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrderShipment extends Model
{
    protected $fillable = [
        'job_order_id',
        'service_level',
        'bl_no',
        'eta',
        'etd',
        'hs_code',
        'rod',
        'permits',
        'shipment_remarks',
        'target_delivery_date',
        'target_completion_date',
        'commitment_remarks',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }
}
