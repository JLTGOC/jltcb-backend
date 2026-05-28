<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAddress extends Model
{
    protected $fillable = [
        'company_id',
        'registered_address',
        'office_address',
        'usual_port',
        'origin_country',
        'destination_country',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
