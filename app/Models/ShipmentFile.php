<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentFile extends Model
{
    protected $fillable = [
        'shipment_id',
        'quotation_file_id'
    ];

    protected $hidden = [
        'created_by',
        'updated_by'
    ];

    public function shipment() {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function quotationFile() {
        return $this->belongsTo(QuotationFile::class, 'quotation_file_id');
    }
}
