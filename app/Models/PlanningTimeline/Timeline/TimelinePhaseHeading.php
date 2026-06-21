<?php

namespace App\Models\PlanningTimeline\Timeline;

use Illuminate\Database\Eloquent\Model;

class TimelinePhaseHeading extends Model
{
    protected $table = 'planning_timeline_phase_headings';

    protected $fillable = ['timeline_phase_id', 'name', 'key', 'input_type', 'sort_order'];

    public function phase() {
        return $this->belongsTo(TimelinePhase::class, 'timeline_phase_id');
    }

    public function taskValue() {
        return $this->hasOne(TimelineTaskValue::class, 'timeline_phase_id');
    }
}
