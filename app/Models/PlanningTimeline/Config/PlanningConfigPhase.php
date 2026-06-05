<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;

class PlanningConfigPhase extends Model
{
    protected $fillable = ['name'];
    
    public function templatePhases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'config_phase_id');
    }
}
