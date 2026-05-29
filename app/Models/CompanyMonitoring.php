<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyMonitoring extends Model
{
    protected $fillable = [
        'company_id',
        'past_issues',
        'penalties',
        'custom_flags',
        'payment_delays',
        'claims',
        'notes'
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
