<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInsight extends Model
{
    protected $fillable = [
        'company_id',
        'growth',
        'expansion_plan',
        'competitors',
        'opportunities',
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
