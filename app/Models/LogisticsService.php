<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsService extends Model
{
    protected $fillable = [
        'quotation_id',
        // 'service_type',
        'transport_mode',
        'service_options',
        'commodity',
        'cargo_type',
        'container_size',
        'origin',
        'destination',
        'remarks',
    ];

    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }
}
