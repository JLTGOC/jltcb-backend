<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyContact extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'full_name',
        'position',
        'contact_number',
        'email',
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
