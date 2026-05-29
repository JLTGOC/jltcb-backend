<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientClassification extends Model
{
    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    public function companies()
    {
        return $this->hasMany(Company::class, 'client_classification_id');
    }
}
