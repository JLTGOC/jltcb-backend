<?php

namespace App\Models\PlanningTimeline\Timeline;

use Illuminate\Database\Eloquent\Model;

class TimelineProcess extends Model
{
    protected $table = 'planning_timeline_processes';

    protected $fillable = ['name', 'timeline_phase_id'];

    public function phase() {
        return $this->belongsTo(TimelinePhase::class, 'timeline_phase_id');
    }

    public function tasks() {
        return $this->hasMany(TimelineTask::class, 'timeline_process_id');
    }
}
