<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyIndustry extends Model
{
    protected $fillable = [
        'company_id',
        'industry_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
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
