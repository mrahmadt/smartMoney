<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Illuminate\Support\Facades\Auth;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, HasPushSubscriptions;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'budgets',
        'alertNewBillCreation',
        'alertBillOverAmountPercentage',
        'alertAbnormalTransaction',
        'alertViaEmail',
        'is_admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'password' => 'hashed',
            'budgets' => 'array',
            'alertViaEmail' => 'boolean',
            'alertNewBillCreation' => 'boolean',
            'alertBillOverAmountPercentage' => 'boolean',
            'alertAbnormalTransaction' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function generateToken($name, $deleteOld = true, $sessinName = null){
        $authUser = Auth::user();
        if($deleteOld) $authUser->tokens()->where(['tokenable_type'=>'App\Models\User','tokenable_id'=> $authUser->id,'name'=>$name])->delete();
        $token =  $authUser->createToken($name)->plainTextToken; 
        if($sessinName) session()->put($sessinName, $token);
        return $token;
    }

}
