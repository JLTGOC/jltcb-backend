<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegulatoryService extends Model
{
    protected $fillable = [
        'quotation_id',
        'full_name',
        'contact_person_contact_number',
        'business_type',
        'type_of_regulatory_assistance',
        'application_type',
        'message'
    ];

    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }
}
