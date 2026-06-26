<?php

namespace App\Models\PlanningTimeline\Timeline;

use App\Models\JobOrder;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    protected $table = 'planning_timelines';

    protected $fillable = ['job_order_id', 'created_by', 'planning_template_id'];

    public function jobOrder() {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template() {
        return $this->belongsTo(PlanningTemplate::class, 'planning_template_id');
    }
    
    public function phases() {
        return $this->hasMany(TimelinePhase::class, 'planning_timeline_id');
    }

    public function documents() {
        return $this->hasMany(TimelineDocument::class, 'planning_timeline_id');
    }
}
