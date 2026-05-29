<?php

namespace App\Models\QuotationTemplateConfig;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['template_name', 'message'];
}
