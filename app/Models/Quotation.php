<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Quotation extends Model
{
     protected $fillable = [
        'reference_number',
        'client_id',
        'as_id',
        'status',
        'contact_person',
        'contact_number',
        'email',
        'company_name',
        'company_address',
        'service_type',
        'transport_mode',
        'service_options',
        'commodity',
        'cargo_type',
        'cargo_volume',
        'container_size',
        'origin',
        'destination'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function accountSpecialist() {
        return $this->belongsTo(User::class, 'as_id');
    }

    public function files() {
        return $this->hasMany(QuotationFile::class);
    }
}