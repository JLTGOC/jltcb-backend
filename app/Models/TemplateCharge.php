<?php

namespace App\Models;

use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateConfig\BillingConfiguration;
use Illuminate\Database\Eloquent\Model;

class TemplateCharge extends Model
{
    protected $fillable = ['template_id', 'name'];

    public function template() {
        return $this->belongsTo(QuotationTemplate::class, 'template_id');
    }

    public function allowedReceiptCharges() {
        return $this->belongsToMany(
            BillingConfiguration::class,
            'template_charge_receipt_options',
            'template_charge_id',
            'billing_config_id'
        );
    }
}
