<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Shipment extends Model implements Searchable
{
    protected $fillable = [
        'reference_number',
        'quotation_id',
        'job_order_id',
        'client_id',
        'as_id',
        'status',
        'contact_person',
        'contact_number',
        'email',
        'company_name',
        'commodity',
        'cargo_type',
        'container_size',
        'origin',
        'destination',
        'remarks',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->reference_number,
         );
    }


    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function accountSpecialist() {
        return $this->belongsTo(User::class, 'as_id');
    }

    public function quotation() {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function shipmentFile() {
        return $this->hasMany(ShipmentFile::class, 'shipment_id');
    }

    public function jobOrder() {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    protected $casts = [
        'quotation_id' => 'integer',
        'client_id' => 'integer',
        'as_id' => 'integer',
    ];
}
