<?php

namespace App\Models\PlanningTimeline\Timeline;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TimelineDocument extends Model
{
    protected $table = 'planning_timeline_documents';

    protected $fillable = ['name', 'type', 'file_type', 'file_path', 'status', 'uploaded_by', 'planning_timeline_id'];

    public function uploadedBy() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function timeline() {
        return $this->belongsTo(Timeline::class, 'planning_timeline_id');
    }
}
