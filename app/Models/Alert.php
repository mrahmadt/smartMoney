<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Notifications\WebPush;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
    ];

    public static function createAlert($title, $message, $type = 'info', $user_id = null, $pushNotification = true)
    {
        $alert = new Alert();
        $alert->title = $title;
        $alert->user_id = $user_id;
        $alert->message = $message;
        $alert->type = $type;
        $alert->save();
        if($user_id && $pushNotification){
            $user = User::find($user_id);
            $user->notify(new WebPush($title, $message));
        }
    }
}
