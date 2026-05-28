<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyWarehouseAddress extends Model
{
    protected $fillable = [
        'company_id',
        'address',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
