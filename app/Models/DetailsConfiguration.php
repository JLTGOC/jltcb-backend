<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
            'template_detail_config',
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
