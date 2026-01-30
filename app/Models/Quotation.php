<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Quotation extends Model
{
    protected $fillable = [
        'reference_number',
        'user_id',
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
        'cargo_volume',
        'container_size',
        'origin',
        'destination'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->morphMany(Message::class, 'reference');
    }
}
