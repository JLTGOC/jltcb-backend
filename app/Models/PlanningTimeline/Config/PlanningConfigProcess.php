<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;

class PlanningConfigProcess extends Model
{
    protected $fillable = ['name', 'config_version_id'];

    public function version() {
        return $this->belongsTo(PlanningConfigVersion::class, 'config_version_id');
    }

    public function templateProcesses() {
        return $this->hasMany(PlanningTemplateProcess::class, 'config_process_id');
    }
}
