<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use App\Models\IssuedQuotation\IssuedQuotation;

class Quotation extends Model implements Searchable
{
     protected $fillable = [
        'reference_number',
        'service_type_id',
        'client_id',
        'client_name',
        'as_id',
        'status',
        'service_options',
        'commodity',
        'contact_person',
        'contact_number',
        'email',
        'company_name',
        'company_address',
        'position',
        'consignee',
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

    public function serviceType() {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    protected $casts = [
        'client_id' => 'integer',
        'as_id' => 'integer',
        'service_type_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $quotation) {
            if (
                $quotation->isDirty('status') &&
                $quotation->status === 'RESPONDED' &&
                $quotation->getOriginal('status') !== 'RESPONDED'
            ) {
                if (preg_match('/^RQ-[^-]+-\d{8}-(\d+)$/', $quotation->reference_number ?? '', $matches)) {
                    $quotation->reference_number = 'QT-' . Carbon::now()->format('m-Y') . '-' . $matches[1];
                }
            }
        });
    }
}
