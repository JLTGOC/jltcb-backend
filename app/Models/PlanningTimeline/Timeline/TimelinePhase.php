<?php

namespace App\Models\PlanningTimeline\Timeline;

use Illuminate\Database\Eloquent\Model;

class TimelinePhase extends Model
{
    protected $table = 'planning_timeline_phases';

    protected $fillable = ['planning_timeline_id', 'name', 'sort_order'];

    public function timeline() {
        return $this->belongsTo(Timeline::class, 'planning_timeline_id');
    }

    public function headings() {
        return $this->hasMany(TimelinePhaseHeading::class, 'timeline_phase_id');
    }

    public function processes() {
        return $this->hasMany(TimelineProcess::class, 'timeline_phase_id');
    }
}
