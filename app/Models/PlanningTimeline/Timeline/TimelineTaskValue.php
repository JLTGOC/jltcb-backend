<?php

namespace App\Models\PlanningTimeline\Timeline;

use Illuminate\Database\Eloquent\Model;

class TimelineTaskValue extends Model
{
    protected $table = 'planning_timeline_task_values';

    protected $fillable = ['planning_timeline_task_id', 'timeline_phase_heading_id', 'value'];

    public function task() {
        return $this->belongsTo(TimelineTask::class, 'planning_timeline_task_id');
    }

    public function phaseHeading() {
        return $this->belongsTo(TimelinePhaseHeading::class, 'timeline_phase_heading_id');
    }
}
