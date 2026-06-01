<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;

class PlanningConfigPhase extends Model
{
    protected $fillable = ['name', 'planning_template_id', 'sort_order'];

    public function planningTemplate() {
        return $this->belongsTo(PlanningTemplate::class, 'planning_template_id');
    }

    public function templatePhases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'config_phase_id');
    }
}
