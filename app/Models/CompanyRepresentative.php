<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyRepresentative extends Model
{
    protected $fillable = [
        'company_id',
        'full_name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
