<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'service',
    ];

    public function serviceOptions()
    {
        return $this->hasMany(ServiceOption::class, 'service_type_id');
    }
}
