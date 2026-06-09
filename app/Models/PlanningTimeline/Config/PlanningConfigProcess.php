<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;

class PlanningConfigProcess extends Model
{
    protected $fillable = ['name', 'config_version_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }

    public function templateProcesses() {
        return $this->hasMany(PlanningTemplateProcess::class, 'config_process_id');
    }

    public function isLocked() : bool {
        return $this->templateProcesses()->exists();
    }

    public function lockingTemplates() {
        return PlanningTemplate::whereHas(
            'phases.processes',
            fn($q) => $q->where('config_process_id', $this->id)
        )->get();
    }
}
