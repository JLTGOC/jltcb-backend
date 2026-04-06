<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    protected $fillable = [
        'reference_number',
        'job_type',
        'client_id',
        'as_id',
        'operations_id',
        'finance_id',
        'quotation_id',
        'subject',
        'email_body',
        'client_type',
        'accredited',
        'client_remarks',
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
        'terms_of_payment',
        'billing_date',
        'shall_be_billed',
        'shipment_creation_status'
    ];

    protected $casts = [
        'client_id' => 'integer',
        'as_id' => 'integer',
        'operations_id' => 'integer',
        'finance_id' => 'integer',
        'quotation_id' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function accountSpecialist()
    {
        return $this->belongsTo(User::class, 'as_id');
    }

    public function operations()
    {
        return $this->belongsTo(User::class, 'operations_id');
    }

    public function finance()
    {
        return $this->belongsTo(User::class, 'finance_id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }
}
