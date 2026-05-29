<?php

namespace App\Models\IssuedQuotation;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationCharge extends Model
{
    protected $fillable = [
        'issued_quotation_id',  
        'name',
        'subtotal'
    ];

    public function issuedQuotation()
    {
        return $this->belongsTo(IssuedQuotation::class);
    }

    public function items() {
        return $this->hasMany(IssuedQuotationChargeItems::class, 'issued_quotation_charge_id');
    }
}
