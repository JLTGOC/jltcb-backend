<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class JobOrder extends Model implements Searchable
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
        'shipment_creation_status',
        'assignment_status',
        'assigned_at',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'as_id' => 'integer',
        'operations_id' => 'integer',
        'finance_id' => 'integer',
        'quotation_id' => 'integer',
        'assignment_status' => 'string',
        'assigned_at' => 'datetime',
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
            null
        );
    }

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

    public function jobOrderBilling()
    {
        return $this->hasOne(JobOrderBilling::class, 'job_order_id');
    }

    public function jobOrderShipment()
    {
        return $this->hasOne(JobOrderShipment::class, 'job_order_id');
    }

    public function jobOrderClient()
    {
        return $this->hasOne(JobOrderClient::class, 'job_order_id');
    }

    public function shipment() {
        return $this->hasOne(Shipment::class, 'job_order_id');
    }
}
