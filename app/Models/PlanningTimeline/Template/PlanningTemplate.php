<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PlanningTemplate extends Model
{
    protected $fillable = ['name', 'service_category', 'service_type', 'status', 'created_by', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function phases() {
        return $this->hasMany(PlanningTemplatePhase::class, 'planning_template_id');
    }
}
