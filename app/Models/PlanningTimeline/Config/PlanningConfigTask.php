<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplateTask;

class PlanningConfigTask extends Model
{
    protected $fillable = ['name', 'planning_template_id'];

    public function planningTemplate() {
        return $this->belongsTo(PlanningTemplate::class, 'planning_template_id');
    }

    public function templateTasks() {
        return $this->hasMany(PlanningTemplateTask::class, 'config_task_id');
    }
}
