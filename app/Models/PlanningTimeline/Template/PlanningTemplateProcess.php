<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use Illuminate\Database\Eloquent\Model;

class PlanningTemplateProcess extends Model
{
    protected $fillable = ['template_phase_id', 'config_process_id'];

    public function phase() {
        return $this->belongsTo(PlanningTemplatePhase::class, 'template_phase_id');
    }

    public function configProcess() {
        return $this->belongsTo(PlanningConfigProcess::class, 'config_process_id');
    }

    public function tasks() {
        return $this->hasMany(PlanningTemplateTask::class, 'template_process_id');
    }
}
