<?php

namespace App\Models\IssuedQuotation;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationChargeItems extends Model
{
    protected $fillable = [
        'issued_quotation_charge_id',  
        'receipt_charge_label',
        'uom',
        'amount',
        'quantity',
        'container_size'
    ];

    public function charge() {
        return $this->belongsTo(IssuedQuotationCharge::class, 'issued_quotation_charge_id');
    }
}
