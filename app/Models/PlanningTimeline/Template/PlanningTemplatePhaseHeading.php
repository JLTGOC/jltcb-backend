<?php

namespace App\Models\PlanningTimeline\Template;

use App\Models\PlanningTimeline\Template\PlanningTemplatePhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PlanningTemplatePhaseHeading extends Model
{
    protected $fillable = [
        'template_phase_id', 'name', 'input_type', 'sort_order', 'key'
    ];

    public function phase() {
        return $this->belongsTo(PlanningTemplatePhase::class, 'template_phase_id');
    }

    public function scopeDefaults(Builder $query) {
        $query->whereNot('key', null);
    }

    public function scopeCustoms(Builder $query) {
        $query->where('key', null);
    }

    public function isDefault() : bool {
        return $this->key ? true : false;
    }
}
