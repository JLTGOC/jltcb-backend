<?php

namespace App\Models\QuotationTemplateConfig;

use Illuminate\Database\Eloquent\Model;

class StandardConfiguration extends Model
{
    protected $table = 'standard_configurations';

    protected $fillable = [
        'template_name', 'policies', 'terms_and_conditions', 'banking_details', 'footer'
    ];
}
