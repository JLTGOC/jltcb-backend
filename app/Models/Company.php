<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'consignee_used',
        'trade_name',
        'account_handler_id',
        'transaction_type_id',
        'client_classification_id',
        'company_type_id',
        'business_type_id',
        'business_registration_number',
        'website',
        'years_of_operation',
        'activation_date',
    ];

    public function accountHandler()
    {
        return $this->belongsTo(User::class, 'account_handler_id');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    public function clientClassification()
    {
        return $this->belongsTo(ClientClassification::class, 'client_classification_id');
    }

    public function companyType()
    {
        return $this->belongsTo(CompanyType::class, 'company_type_id');
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function companyIndustries()
    {
        return $this->hasMany(CompanyIndustry::class, 'company_id');
    }

    public function address()
    {
        return $this->hasOne(CompanyAddress::class, 'company_id');
    }

    public function warehouseAddresses()
    {
        return $this->hasMany(CompanyWarehouseAddress::class, 'company_id');
    }

    public function deliveryAddresses()
    {
        return $this->hasMany(CompanyDeliveryAddress::class, 'company_id');
    }

    public function contacts()
    {
        return $this->hasMany(CompanyContact::class, 'company_id');
    }

    public function representatives()
    {
        return $this->hasMany(CompanyRepresentative::class, 'company_id');
    }

    public function registration()
    {
        return $this->hasOne(CompanyRegistration::class, 'company_id');
    }

    public function pricing()
    {
        return $this->hasOne(CompanyPricing::class, 'company_id');
    }

    public function operation()
    {
        return $this->hasOne(CompanyOperation::class, 'company_id');
    }

    public function monitoring()
    {
        return $this->hasOne(CompanyMonitoring::class, 'company_id');
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class, 'company_id');
    }

    public function insight()
    {
        return $this->hasOne(CompanyInsight::class, 'company_id');
    }
}
