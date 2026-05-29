<?php

namespace App\Models;

use App\Models\QuotationTemplate\QuotationTemplate;
use Illuminate\Database\Eloquent\Model;

class QuotationField extends Model
{
    protected $fillabel = ['quotation_type', 'field_name', 'display_name'];

    public function templates() {
        return $this->belongsToMany(
            QuotationTemplate::class,
            'template_client_input_configs',
            'quotation_field_id',
            'template_id'
        );
    }

    public function scopeRegulatoryFields($query) {
        $query->where('quotation_type', 'REGULATORY');
    }

    public function scopeLogisticsFields($query) {
        $query->where('quotation_type', 'LOGISTICS');
    }
}
