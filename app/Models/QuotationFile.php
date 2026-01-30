<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFile extends Model
{

    protected $fillable = ['quotation_id', 'file_path'];

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }
}
