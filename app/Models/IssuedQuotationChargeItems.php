<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationChargeItems extends Model
{
    protected $fillable = [
        'issued_quotation_charge_id',  
        'receipt_charge_label',
        'currency_label',
        'uom_label',
        'amount'
    ];

    public function charge() {
        return $this->belongsTo(IssuedQuotationCharge::class, 'issued_quotation_charge_id');
    }
}
