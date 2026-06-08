<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;

class PlanningConfigVersion extends Model
{
    protected $fillable = [
        'version_number', 'service_category', 
    ];

    public function phases() {
        return $this->hasMany(PlanningConfigPhase::class, 'config_version_id');
    }

    public function processes() {
        return $this->hasMany(PlanningConfigProcess::class, 'config_version_id');
    }

    public function tasks() {
        return $this->hasMany(PlanningConfigTask::class, 'config_version_id');
    }
}
