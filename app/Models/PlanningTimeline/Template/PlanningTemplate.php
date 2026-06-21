<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Timeline\Timeline;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Model;

class PlanningTemplate extends Model
{
    protected $fillable = ['name', 'service_category', 'service_type_id', 'is_active', 'version_number'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function phases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'planning_template_id')->orderBy('sort_order');
    }

    public function serviceType() {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function timelines() {
        return $this->hasMany(Timeline::class, 'planning_template_id');
    }
}
