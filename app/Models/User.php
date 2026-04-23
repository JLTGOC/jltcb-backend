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

class User extends Authenticatable
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
        'company_name',
        'company_address',
        'company_position',
        'business_type',
        'image_path',
        'id_image_path',
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

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function quotations() {
        return $this->hasMany(Quotation::class, 'client_id');
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
}
