<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Quotation extends Model implements Searchable
{
     protected $fillable = [
        'reference_number',
        'client_id',
        'as_id',
        'status',
        'contact_person',
        'contact_number',
        'email',
        'company_name',
        'company_address',
        'position',
        'assignment_status',
        'assigned_at',
        'created_by'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
            null
        );
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function accountSpecialist() {
        return $this->belongsTo(User::class, 'as_id');
    }

    public function messages()
    {
        return $this->morphMany(Message::class, 'reference');
    }
    
    public function files() {
        return $this->hasMany(QuotationFile::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logisticsService() {
        return $this->hasOne(LogisticsService::class);
    }

    public function regulatoryService() {
        return $this->hasOne(RegulatoryService::class);
    }

    public function issuedQuotations() {
        return $this->hasOne(IssuedQuotation::class);
    }

    public function reassignmentRequests() {
        return $this->hasMany(ReassignmentRequest::class);
    }

    public function latestReassignmentRequest() {
        return $this->hasOne(ReassignmentRequest::class)->latestOfMany();
    }

    public function jobOrder() {
        return $this->hasOne(JobOrder::class, 'quotation_id');
    }

    public function shipment() {
        return $this->hasOne(Shipment::class, 'quotation_id');
    }

    public function activities() {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    protected $casts = [
        'client_id' => 'integer',
        'as_id' => 'integer',
    ];
}
