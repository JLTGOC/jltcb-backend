<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReassignmentRequest extends Model
{
    protected $fillable = [
        'quotation_id',
        'job_order_id',
        'as_id',
        'ops_id',
        'reason',
        'additional_details',
        'status',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function accountSpecialist()
    {
        return $this->belongsTo(User::class, 'as_id');
    }

    public function operations()
    {
        return $this->belongsTo(User::class, 'ops_id');
    }
}
