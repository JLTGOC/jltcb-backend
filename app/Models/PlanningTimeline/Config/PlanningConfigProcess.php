<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;

class PlanningConfigProcess extends Model
{
    protected $fillable = ['name', 'config_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function config() {
        return $this->belongsTo(PlanningTemplateConfig::class, 'config_id');
    }

    public function templateProcesses() {
        return $this->hasMany(PlanningTemplateProcess::class, 'config_process_id');
    }

    public function isLocked() : bool {
        return $this->relationLoaded('templateProcesses')
            ? $this->templateProcesses->isNotEmpty() 
            : $this->templateProcesses()->exists();
    }
}
