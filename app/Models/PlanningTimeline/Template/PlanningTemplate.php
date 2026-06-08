<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use Illuminate\Database\Eloquent\Model;

class PlanningTemplate extends Model
{
    protected $fillable = ['name', 'service_category', 'service_type_id', 'is_active', 'version_number'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function phases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'planning_template_id');
    }
}
