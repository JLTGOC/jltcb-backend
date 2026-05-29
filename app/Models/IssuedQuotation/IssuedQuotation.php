<?php

namespace App\Models\IssuedQuotation;

use App\Models\Quotation;
use App\Models\QuotationTemplate\QuotationTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class IssuedQuotation extends Model
{
    protected $fillable = [
        'quotation_id',
        'template_id',
        'issued_by',
        'subject',
        'message',
        'rate_validity',
        'uom',
        'currency'
    ];

    public function getDaysUntilExpirationAttribute() : ?int {
        return Carbon::now()->diffInDays($this->rate_validity);
    }

    public function shouldShowExpirationWarning() : bool {
        $expirationWarningDays = 7;

        return $this->quotation->status !== 'ACCEPTED'
            && $this->days_until_expiration >= 0
            && $this->days_until_expiration <= $expirationWarningDays;
    }

    public function getExpirationStatusAttribute(): ?string
    {
        if ($this->quotation->status === 'ACCEPTED') {
            return null;
        }

        $expirationWarningDays = 7;
        $days = $this->days_until_expiration;

        if ($days < 0) {
            return 'Expired';
        }

        if ($days <= $expirationWarningDays) {
            return 'Soon to Expired';
        }

        return null;
    }

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
        'rate_validity' => 'datetime',
    ];
}