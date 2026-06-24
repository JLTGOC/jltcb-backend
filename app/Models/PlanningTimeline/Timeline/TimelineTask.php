<?php

namespace App\Models\PlanningTimeline\Timeline;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TimelineTask extends Model
{
    protected $table = 'planning_timeline_tasks';

    protected $fillable = ['name', 'is_complete', 'timeline_process_id'];

    protected $casts = [
        'is_complete' => 'boolean'
    ];

    public function process() {
        return $this->belongsTo(TimelineProcess::class, 'timeline_process_id');
    }

    public function values() {
        return $this->hasMany(TimelineTaskValue::class, 'timeline_task_id');
    }

    public function assignees() {
        return $this->belongsToMany(
            User::class, 
            'planning_timeline_task_assignees',
            'timeline_task_id',
            'user_id' 
        );
    }
}
