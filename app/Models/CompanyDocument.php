<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDocument extends Model
{
    protected $fillable = [
        'company_id',
        'filepath',
        'file_name',
        'file_type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
