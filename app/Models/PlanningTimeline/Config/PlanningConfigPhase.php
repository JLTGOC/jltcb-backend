<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;

class PlanningConfigPhase extends Model
{
    protected $fillable = ['name', 'config_version_id'];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }
    
    public function templatePhases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'config_phase_id');
    }
}
