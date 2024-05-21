<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\fireflyIII;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory;
    protected $table = 'accounts';
    protected $fillable = [
        'FF_account_id',
        'FF_account_name',
        'FF_account_type',
        'FF_account_name_change_to',
        'account_code',
        'sms_sender',
        'budget_id',
        'alertNewTransactions',
        // 'alertAbnormalTransaction',
        // 'abnormalTransactionPercentage',
        'user_id',
        'defaultAccount',
        'tags',
        'values',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alertNewTransactions' => 'boolean',
            // 'alertAbnormalTransaction' => 'boolean',
            'defaultAccount' => 'boolean',
            'tags' => 'array',
            'values' => 'array',
        ];
    }

    public static function lookupAccountByCode($sender, $account_code, $fireflyIII = null){
        if($fireflyIII == null) $fireflyIII = new fireflyIII();

        $account_code = self::cleanAccount($account_code);
        if($account_code == '') return false;

        $account = Account::where('sms_sender', $sender)->where('account_code', $account_code)->first();
        if(!$account) return false;

        $FF_account = $fireflyIII->getAccount($account->FF_account_id);
        if($FF_account){
            $FF_account->data->attributes->id = $FF_account->data->id;
            $FF_account = $FF_account->data->attributes;
            return ['account'=>$account, 'FF_account'=>$FF_account];
        }
        return false;

    }
    public static function cleanAccount($account){
        if($account == '') return $account;
        $accountClean = str_replace(['x','X','*','#'],'', $account);
        if(strlen($accountClean) == 0) return $account;
        return $accountClean;
    }

    public static function isReplaceWithFFAccount($account){
        // do we have /replaceWith:"([^"]*)"/m in the notes
        // if yes, return the value in the brackets
        // else return false
        $pattern = '/replaceWith:"([^"]*)"/m';
        $notes = $account->attributes->notes;
        if (preg_match($pattern, $notes, $matches)) {
            return $matches[1];
        }
        return false;
    }

    public static function billOverAmountPercentage($account){
        $pattern = '/alertBillOverAmountPercentage:"([^"]*)"/m';
        $notes = $account->attributes->notes;
        if (preg_match($pattern, $notes, $matches)) {
            if(!is_numeric($matches[1])) return false;
            return (float)$matches[1];
        }
        return false;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
