<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    public function companies()
    {
        return $this->hasMany(Company::class, 'transaction_type_id');
    }
}
