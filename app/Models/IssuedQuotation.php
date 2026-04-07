<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedQuotation extends Model
{
    protected $fillable = [
        'quotation_id',
        'template_id',
        'issued_by',
        'subject',
        'message',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function template()
    {
        return $this->belongsTo(QuotationTemplate::class, 'template_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function detailValues()
    {
        return $this->hasMany(IssuedQuotationDetailValue::class, 'issued_quotation_id');
    }

    public function charges()
    {
        return $this->hasMany(IssuedQuotationCharge::class, 'issued_quotation_id');
    }
    
    public function standardConfig()
    {
        return $this->hasOne(IssuedQuotationStandardConfig::class, 'issued_quotation_id');
    }

    public function authorizedSignatory() {
        return $this->hasOne(AuthorizedSignatories::class, 'issued_quotation_id');
    }

    protected $casts = [
        'issued_by' => 'integer',
    ];
}