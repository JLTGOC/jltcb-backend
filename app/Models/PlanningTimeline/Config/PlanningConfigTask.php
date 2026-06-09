<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateTask;

class PlanningConfigTask extends Model
{
    protected $fillable = ['name', 'config_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function config() {
        return $this->belongsTo(PlanningTemplateConfig::class, 'config_id');
    }

    public function templateTasks() {
        return $this->hasMany(PlanningTemplateTask::class, 'config_task_id');
    }

    public function isLocked() : bool {
        return $this->relationLoaded('templateTasks')
            ? $this->templateTasks->isNotEmpty()
            : $this->templateTasks()->exists();
    }
}
