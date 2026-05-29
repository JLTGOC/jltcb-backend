<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDeliveryAddress extends Model
{
    protected $fillable = [
        'company_id',
        'address',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
