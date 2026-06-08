<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateTask;
use App\Models\PlanningTimeline\Template\PlanningTemplate;

class PlanningConfigTask extends Model
{
    protected $fillable = ['name', 'is_locked', 'config_version_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }

    public function templateTasks() {
        return $this->hasMany(PlanningTemplateTask::class, 'config_task_id');
    }

    public function lockingTemplates() {
        return PlanningTemplate::whereHas(
            'phases.processes.tasks',
            fn($q) => $q->where('config_task_id', $this->id)
        )->get();
    }
}
