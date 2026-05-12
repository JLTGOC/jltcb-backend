<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentHistory extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'shipment_id',
        'action',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
