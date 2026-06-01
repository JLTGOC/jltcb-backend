<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;

class PlanningConfigProcess extends Model
{
    protected $fillable = ['name', 'planning_template_id'];

    public function planningTemplate() {
        return $this->belongsTo(PlanningTemplate::class, 'planning_template_id');
    }

    public function templateProcesses() {
        return $this->hasMany(PlanningTemplateProcess::class, 'config_process_id');
    }
}
