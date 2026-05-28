<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $fillable = ['name'];

    public function companyIndustries()
    {
        return $this->hasMany(CompanyIndustry::class, 'industry_id');
    }
}
