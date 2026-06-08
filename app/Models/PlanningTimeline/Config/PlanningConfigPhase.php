<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;

class PlanningConfigPhase extends Model
{
    protected $fillable = ['name', 'is_locked', 'config_version_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }
    
    public function templatePhases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'config_phase_id');
    }

    public function lockingTemplates() {
        return PlanningTemplate::whereHas(
            'phases',
            fn($q) => $q->where('config_phase_id', $this->id)
        )->get();
    }
}
