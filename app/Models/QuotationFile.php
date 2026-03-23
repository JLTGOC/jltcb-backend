<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFile extends Model
{

    protected $fillable = ['quotation_id', 'file_path', 'uploaded_by', 'type', 'original_file_name', 'file_type'];


    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }

    public function uploader() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    protected $casts = [
        'quotation_id' => 'integer',
    ];
}
