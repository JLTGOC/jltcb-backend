<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReassignmentRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'quotation_id',
        'job_order_id',
        'as_id',
        'ops_id',
        'reason',
        'additional_details',
        'status',
    ];

    protected $casts = [
        'quotation_id' => 'integer',
        'job_order_id' => 'integer',
        'as_id' => 'integer',
        'ops_id' => 'integer',
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
