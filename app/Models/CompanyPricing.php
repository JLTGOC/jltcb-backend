<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPricing extends Model
{
    protected $fillable = [
        'company_id',
        'service_rate',
        'special_discounts',
        '3pl_profit_range',
        'notes'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
