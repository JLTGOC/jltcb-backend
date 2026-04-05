<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationTemplate extends Model
{
    protected $fillable = ['name', 'service_type', 'is_active'];

    public function templateCharges() {
        return $this->hasMany(TemplateCharge::class, 'template_id');
    }

    public function detailConfigs() {
        return $this->belongsToMany(
            DetailsConfiguration::class,
            'template_detail_configs',
            'template_id',
            'details_config_id'
        );
    }

    public function quotationFields() {
        return $this->belongsToMany(
            QuotationField::class,
            'template_client_input_configs',
            'template_id',
            'quotation_field_id'
        );
    }
}
