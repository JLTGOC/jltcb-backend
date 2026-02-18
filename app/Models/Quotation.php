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
        'service_type',
        'transport_mode',
        'service_options',
        'commodity',
        'cargo_type',
        // 'cargo_volume',
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
}
