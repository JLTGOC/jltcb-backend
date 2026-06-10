<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use Illuminate\Database\Eloquent\Model;

class PlanningTemplatePhase extends Model
{
    protected $fillable = ['planning_template_id', 'config_phase_id', 'sort_order'];

    public function template() {
        return $this->belongsTo(PlanningTemplate::class, 'planning_template_id');
    }

    public function configPhase() {
        return $this->belongsTo(PlanningConfigPhase::class, 'config_phase_id');
    }

    public function processes() {
        return $this->hasMany(PlanningTemplateProcess::class, 'template_phase_id');
    }

    public function headings() {
        return $this->hasMany(PlanningTemplatePhaseHeading::class, 'template_phase_id')->orderBy('sort_order');
    }
}
