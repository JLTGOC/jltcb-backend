<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationFile extends Model
{

    protected $fillable = ['quotation_id', 'document_checklist_item_id', 'file_path', 'uploaded_by', 'type', 'original_file_name', 'file_type'];


    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }

    public function documentChecklistItem() {
        return $this->belongsTo(QuotationFileChecklistItem::class, 'document_checklist_item_id');
    }

    public function uploader() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    protected $casts = [
        'quotation_id' => 'integer',
        'document_checklist_item_id' => 'integer',
    ];
}
