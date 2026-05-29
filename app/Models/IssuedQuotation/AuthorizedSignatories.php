<?php

namespace App\Models\IssuedQuotation;

use Illuminate\Database\Eloquent\Model;

class AuthorizedSignatories extends Model
{
    protected $fillable = [
        'issued_quotation_id',
        'closing_statement',
        'is_authorized_signatory',
        'authorized_signatory_name',
        'position',
        'signature_file_path',
    ];

    public function issuedQuotation() {
        return $this->belongsTo(IssuedQuotation::class, 'issued_quotation_id');
    }
}
