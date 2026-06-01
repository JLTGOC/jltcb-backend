<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use Illuminate\Database\Eloquent\Model;

class PlanningTemplateTask extends Model
{
    protected $fillable = ['template_process_id', 'config_task_id'];

    public function process() {
        return $this->belongsTo(PlanningTemplateProcess::class, 'template_process_id');
    }

    public function configTask() {
        return $this->belongsTo(PlanningConfigTask::class, 'config_task_id');
    }
}
