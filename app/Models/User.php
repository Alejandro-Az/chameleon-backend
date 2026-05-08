<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;


class User extends Authenticatable implements JWTSubject, CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, CanResetPassword, \Illuminate\Auth\MustVerifyEmail;

    protected $guard_name = 'api';

    public function getJWTIdentifier()
    {
        return $this->getKey(); // normalmente el id
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordLinkNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify((new \App\Notifications\VerifyEmailLinkNotification())->afterCommit());
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id','name','email','username','password','status','suspended_at','timezone','locale','meta'
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at'      => 'datetime',
            'password'          => 'hashed',
            'meta'              => 'array',
            'type'              => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->public_id)) {
                $user->public_id = (string) Str::ulid();
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function appointmentStaffProfile(): HasOne
    {
        return $this->hasOne(AppointmentStaffProfile::class);
    }

    public function appointmentServices(): BelongsToMany
    {
        return $this->belongsToMany(AppointmentService::class, 'appointment_service_staff', 'user_id', 'appointment_service_id')
            ->withTimestamps();
    }

    public function assignedAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_employee_user_id');
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by_user_id');
    }

    public function scopeServiceAccount($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeHuman($query)
    {
        return $query->where('type', 'human');
    }
}
