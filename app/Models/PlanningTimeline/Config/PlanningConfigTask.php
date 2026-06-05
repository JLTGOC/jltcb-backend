<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateTask;

class PlanningConfigTask extends Model
{
    protected $fillable = ['name', 'config_version_id'];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }

    public function templateTasks() {
        return $this->hasMany(PlanningTemplateTask::class, 'config_task_id');
    }
}
