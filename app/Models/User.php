<?php

namespace Horsefly;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Http\Kernel;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Authorizable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * Only safe user-supplied fields are mass-assignable.
     * Privilege-sensitive columns (is_admin, is_active) must be set
     * explicitly via $user->is_admin = 1; $user->save().
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sip_extension',  // FreePBX extension number for this agent
        'sip_password',   // SIP WebRTC password for this agent
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'sip_password',   // never expose SIP credentials in API responses
    ];


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
    public function getFormattedNameAttribute()
    {
        return ucwords(strtolower($this->name));
    }
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, h:i A') : '-';
    }
    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d M Y, h:i A') : '-';
    }
    public function ip_addresses()
    {
        return $this->hasMany(IpAddress::class);
    }
    public function login_details()
    {
        return $this->hasMany(LoginDetail::class);
    }
    public function audits()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }
    public function performedAudits()
    {
        return $this->hasMany(Audit::class, 'user_id');
    }

    public function cv_notes()
    {
        return $this->hasMany(CVNote::class, 'user_id');
    }
    public function crm_notes()
    {
        return $this->hasMany(CrmNote::class, 'user_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function offices()
    {
        return $this->hasMany(Office::class, 'user_id');
    }
    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'user_id');
    }
    public function sales()
    {
        return $this->hasMany(Sale::class, 'user_id');
    }
}
