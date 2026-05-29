<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    public function companyIndustries()
    {
        return $this->hasMany(CompanyIndustry::class, 'industry_id');
    }
}
