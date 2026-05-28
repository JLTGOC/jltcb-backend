<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['name'];

    public function companies()
    {
        return $this->hasMany(Company::class, 'transaction_type_id');
    }
}
