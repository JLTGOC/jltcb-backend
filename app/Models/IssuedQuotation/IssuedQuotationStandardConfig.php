<?php

namespace App\Models\IssuedQuotation;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationStandardConfig extends Model
{
    protected $fillable = [
        'issued_quotation_id',
        'name',
        'policies',
        'terms_and_conditions',
        'banking_details',
        'footer',
    ];

    public function issuedQuotation()
    {
        return $this->belongsTo(IssuedQuotation::class);
    }
}
