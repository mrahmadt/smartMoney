<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;

class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'budget_id',
        'language',
        'alert_via_email',
        'mfa_secret',
        'mfa_recovery_codes',
        'mfa_required',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
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
            'alert_via_email' => 'boolean',
            'mfa_recovery_codes' => 'array',
            'mfa_required' => 'boolean',
        ];
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->mfa_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->update(['mfa_secret' => $secret]);
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->mfa_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->update(['mfa_recovery_codes' => $codes]);
    }
}
