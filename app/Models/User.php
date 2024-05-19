<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasPushSubscriptions;

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
        'accessAllBudgets',
        'accounts',
        'accessAllAccounts',
        'alertNewBillCreation',
        'alertBillOverAmountPercentage',
        'alertViaEmail',
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
            'accessAllBudgets' => 'boolean',
            'accessAllAccounts' => 'boolean',
            'alertViaEmail' => 'boolean',
            'alertNewBillCreation' => 'boolean',
            'alertBillOverAmountPercentage' => 'boolean',
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
