<?php

namespace App\Models;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'service',
        'code',
        'status',
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

    public function planningTemplates() {
        return $this->hasMany(PlanningTemplate::class, 'service_type_id');
    }

    public function isLocked(): bool
    {
        return $this->serviceOptions()->exists() ||
               $this->quotations()->exists() ||
               $this->jobOrderClients()->exists() ||
               $this->planningTemplates()->exists();
    }
}
