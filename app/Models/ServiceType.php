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

    public function quotations() {
        return $this->hasMany(Quotation::class, 'service_type_id');
    }

    public function jobOrderClients() {
        return $this->hasMany(JobOrderClient::class, 'service_type_id');
    }
}
