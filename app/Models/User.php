<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\{
    Article,
    Quotation
};
use App\Models\IssuedQuotation\IssuedQuotation;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class User extends Authenticatable implements Searchable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'username',
        'email',
        'password',
        // 'password_length',
        'address',
        'contact_number',
        // 'company_name',
        // 'company_address',
        'company_id',
        'company_position',
        // 'business_type',
        'image_path',
        'id_image_path',
        'created_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guard_name = ['sanctum'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult(
            $this,
            $this->id,
            null
        );
    }

    public function scopeClients(Builder $query) {
        return $query->whereHas('roles', fn($q) => $q->where('name', 'Client'));
    }

    public function scopeOldClients(Builder $query) {
        return $query->has('quotations', '>', 1);
    }

    public function scopeNewClients(Builder $query) {
        return $query->has('quotations', '<=', 1);
    }

    // Returns last quotation accepted as an Account Specialist
    public function latestQuotationAccepted() {
        return $this->hasOne(Quotation::class, 'as_id')->latestOfMany();
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function quotations() {
        return $this->hasMany(Quotation::class, 'client_id');
    }

    // Used for user with account specialist role
    public function quotationsAccepted() {
        return $this->hasMany(Quotation::class, 'as_id');
    }
    
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'participants')
            ->withPivot('last_read_at');
    }

    public function files() {
        return $this->hasMany(QuotationFile::class, 'uploaded_by');
    }

    public function shipments() {
        return $this->hasMany(Shipment::class, 'client_id');
    }

    public function issuedQuotations() {
        return $this->hasMany(IssuedQuotation::class, 'issued_by');
    }

    public function reassignmentRequests() {
        return $this->hasMany(ReassignmentRequest::class);
    }

    public function jobOrders() {
        return $this->hasMany(JobOrder::class, 'client_id');
    }

    public function activities() {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
