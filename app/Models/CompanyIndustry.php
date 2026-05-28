<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyIndustry extends Model
{
    protected $fillable = [
        'company_id',
        'industry_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }
}
