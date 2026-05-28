<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientClassification extends Model
{
    protected $fillable = ['name'];

    public function companies()
    {
        return $this->hasMany(Company::class, 'client_classification_id');
    }
}
