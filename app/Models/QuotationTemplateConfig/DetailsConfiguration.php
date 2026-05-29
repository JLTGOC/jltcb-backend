<?php

namespace App\Models\QuotationTemplateConfig;

use App\Models\QuotationTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DetailsConfiguration extends Model
{
    protected $table = 'details_configurations';

    protected $fillable = ['label', 'type'];

    public function dropdownOptions() {
        return $this->hasMany(ConfigDropdownOption::class, 'details_config_id');
    }

    public function templates() {
        return $this->belongsToMany(
            QuotationTemplate::class,
            'template_detail_configs',
            'details_config_id',
            'template_id'
        );
    }

    protected function scopeDropdowns($query) {
        $query->where('type', 'DROPDOWN');
    }

    protected function scopeTextInputs($query) {
        $query->where('type', 'TEXT');
    }

    protected function scopeDatePickers($query) {
        $query->where('type', 'DATE PICKER');
    }
}
