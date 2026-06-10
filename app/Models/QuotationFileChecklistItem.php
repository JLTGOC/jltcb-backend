<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFileChecklistItem extends Model
{
    protected $fillable = [
        'name',
        'visibility',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function quotationFiles() {
        return $this->hasMany(QuotationFile::class, 'document_checklist_item_id');
    }
}
