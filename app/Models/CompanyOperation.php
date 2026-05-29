<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyOperation extends Model
{
    protected $fillable = [
        'company_id',
        'preferred_communication_style',
        'response_time_expectation',
        'client_specific_sop',
        'approval_workflow',
        'pre_alert_details',
        'special_instructions',
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
