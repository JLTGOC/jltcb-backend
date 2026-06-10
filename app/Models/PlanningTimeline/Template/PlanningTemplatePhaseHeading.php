<?php

namespace App\Models\PlanningTimeline\Template;

use Illuminate\Database\Eloquent\Model;

class PlanningTemplatePhaseHeading extends Model
{
    protected $fillable = [
        'template_phase_id', 'name', 'input_type', 'sort_order', 'key'
    ];

    public function phase() {
        return $this->belongsTo(PlanningTemplatePhase::class, 'template_phase_id');
    }
}
