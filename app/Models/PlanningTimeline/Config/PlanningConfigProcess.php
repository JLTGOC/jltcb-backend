<?php

namespace App\Models\PlanningTimeline\Config;

use Illuminate\Database\Eloquent\Model;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplateProcess;

class PlanningConfigProcess extends Model
{
    protected $fillable = ['name'];

    public function templateProcesses() {
        return $this->hasMany(PlanningTemplateProcess::class, 'config_process_id');
    }
}
