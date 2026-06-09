<?php

namespace App\Models\PlanningTimeline\Config;

use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;

class PlanningConfigPhase extends Model
{
    protected $fillable = ['name', 'config_id'];

    protected $casts = [
        'is_locked' => 'boolean'
    ];

    public function config() {
        return $this->belongsTo(PlanningTemplateConfig::class, 'config_id');
    }
    
    public function templatePhases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'config_phase_id');
    }

    public function isLocked() : bool {
        return $this->relationLoaded('templatePhases')
            ? $this->templatePhases->isNotEmpty() 
            : $this->templatePhases()->exists();
    }
}
