<?php

namespace App\Models\IssuedQuotation;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotationDetailValue extends Model
{
    protected $fillable = [
        'issued_quotation_id',
        'template_detail_config_id',
        'label',
        'value',
    ];

    public function issuedQuotation()
    {
        return $this->belongsTo(IssuedQuotation::class, 'issued_quotation_id');
    }
}