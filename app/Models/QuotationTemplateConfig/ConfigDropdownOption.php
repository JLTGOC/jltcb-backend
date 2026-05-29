<?php

namespace App\Models\QuotationTemplateConfig;
use Illuminate\Database\Eloquent\Model;

class ConfigDropdownOption extends Model
{
    protected $table = 'config_dropdown_options';

    protected $fillable = ['name', 'details_config_id'];

    public function detailsConfiguration() {
        return $this->belongsTo(DetailsConfiguration::class, 'details_config_id');
    }
}
