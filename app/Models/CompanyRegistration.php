<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyRegistration extends Model
{
    protected $fillable = [
        'company_id',
        'tin',
        'bir_registration_number',
        'cprs_status',
        'importer_accreditation_number',
        'importer_accreditation_expiry',
        'exporter_registration_number',
        'exporter_registration_expiry',
        'special_permits',
        'compliance_risk'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
