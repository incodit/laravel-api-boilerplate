<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name', 'first_name_x',
        'last_name', 'last_name_x',
        'email', 'email_x',
        'gender', 'gender_x',
        'dob', 'dob_x',
        'stripe_id', 'stripe_id_x',
        'paypal_id', 'paypal_id_x',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'first_name_x',
        'last_name_x',
        'email_x',
        'gender_x',
        'dob_x',
        'stripe_id_x',
        'paypal_id_x',
        'media',
    ];

    protected $appends = [
        'name',
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_name' => 'encrypted',
            'last_name' => 'encrypted',
            'email' => 'encrypted',
            'gender' => 'encrypted',
            'dob' => 'encrypted',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function setSecretPair(string $base, ?string $value, $email = false): void
    {
        if ($value) {
            $plainValue = $email ? mb_strtolower(trim($value)) : trim($value);
            $this->attributes["{$base}_x"] = hash_hmac('sha256', $plainValue, config('app.key'));
        }
    }

    public function setFirstNameXAttribute($value): void
    {
        $this->setSecretPair('first_name', $value);
    }

    public function setLastNameXAttribute($value): void
    {
        $this->setSecretPair('last_name', $value);
    }

    public function setEmailXAttribute($value): void
    {
        $this->setSecretPair('email', $value, true);
    }

    public function setGenderXAttribute($value): void
    {
        $this->setSecretPair('gender', $value);
    }

    public function setDobXAttribute($value): void
    {
        $this->setSecretPair('dob', $value);
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Generate hash for searching _x fields.
     */
    public static function hashForSearch(string $value): string
    {
        return hash_hmac('sha256', trim($value), config('app.key'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar');
    }

    /**
     * Get the e-mail address where password-reset links are sent.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }
}
