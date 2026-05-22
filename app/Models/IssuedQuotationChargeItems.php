<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationChargeItems extends Model
{
    protected $fillable = [
        'issued_quotation_charge_id',  
        'receipt_charge_label',
        'amount',
        'quantity',
        'container_size'
    ];

    public function charge() {
        return $this->belongsTo(IssuedQuotationCharge::class, 'issued_quotation_charge_id');
    }
}
