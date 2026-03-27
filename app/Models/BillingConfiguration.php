<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillingConfiguration extends Model
{
    protected $table = 'billing_configurations';

    protected $fillable = ['label', 'type'];

    protected function scopeReceiptCharges(Builder $query) {
        $query->where('type', 'RECEIPT CHARGES');
    }

    protected function scopeCurrencies(Builder $query) {
        $query->where('type', 'CURRENCY');
    }

    protected function scopeUoms(Builder $query) {
        $query->where('type', 'UOM');
    }
}
