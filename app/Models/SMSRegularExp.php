<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSRegularExp extends Model
{
    use HasFactory;

    protected $table = 'smsregular_exps';

    protected $fillable = [
        'sender_id',
        'transactionType',
        'regularExp',
        'regularExpMD5',
        'stripNewLines',
        'createdBy',
        'data',
        'is_active',
        'is_validTransaction',
        'is_validRegularExp',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'stripNewLines' => 'boolean',
            'is_active' => 'boolean',
            'is_validTransaction' => 'boolean',
            'is_validRegularExp' => 'boolean',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(SMSSender::class, 'sender_id');
    }
    public static function findRegExp($sender_id, $message, $is_validRegularExp = true, $is_validTransaction = true)
    {
        $regularExps = self::where('sender_id', $sender_id)
            ->where('is_active', true)
            ->where('is_validRegularExp', $is_validRegularExp)
            ->where('is_validTransaction', $is_validTransaction)
            ->get(['id', 'sender_id', 'transactionType', 'regularExp', 'stripNewLines']);
        foreach ($regularExps as $regExp) {
            if ($regExp->stripNewLines) $message = preg_replace('/[\r\n]+/', '', $message);
            $regexResult = preg_match($regExp->regularExp, $message, $matches);
            if ($regexResult) {
                $named = array_filter(
                    $matches,
                    fn($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );

                return [
                    'id' => $regExp->id,
                    'sender_id' => $regExp->sender_id,
                    'transactionType' => $regExp->transactionType,
                    'matches' => $named,
                ];
            }
        }
        return false;
    }

    public static function findValidRegExp($sender_id, $message)
    {
        return self::findRegExp(sender_id: $sender_id, message: $message, is_validTransaction: true);
    }

    public static function findInvalidRegExp($sender_id, $message)
    {
        return self::findRegExp(sender_id: $sender_id, message: $message, is_validTransaction: false);
    }

    public static function storeRegularExp($message, $regularExp, $sender_id, $transactionType, $ai_output = null, $isValid = true)
    {
        $regexResult = null;
        $stripNewLines = false;
        $matches = [];
        if ($regularExp !== '' && $transactionType !== '') {
            $regexResult = preg_match($regularExp, $message, $matches);
            if ($regexResult == 0) {
                $message = preg_replace('/[\r\n]+/', '', $message);
                $regexResult = preg_match($regularExp, $message, $matches);
                $stripNewLines = true;
            }

            $is_validRegularExp = false;
            if ($regexResult == 0) {
                $is_validRegularExp = false;
            } elseif (
                isset($matches['amount']) && is_numeric(str_replace(',', '', $matches['amount']))
                && isset($matches['MyAccountNumber']) && $matches['MyAccountNumber'] != ''
                &&
                (
                    (isset($matches['OtherAccountName']) && $matches['OtherAccountName'] != '')
                    ||
                    (isset($matches['OtherAccountNumber']) && $matches['OtherAccountNumber'] != '')
                )
            ) {
                $is_validRegularExp = true;
            }
            $regularExpMD5 = md5($regularExp);
            // check if we have the same regular exp for the same sender already and if so, update it instead of creating a new one
            $existingExp = self::where('sender_id', $sender_id)
                ->where('regularExpMD5', $regularExpMD5)
                ->first();
            if (!$existingExp) {
                $smsregular_exps = [
                    'sender_id' => $sender_id,
                    'transactionType' => $transactionType,
                    'regularExp' => $regularExp,
                    'regularExpMD5' => $regularExpMD5,
                    'stripNewLines' => $stripNewLines,
                    'createdBy' => 'system',
                    'data' => [
                        'sample_smsMessage' => $message,
                        'ai_output' => $ai_output,
                        'regular_expMatches' => $matches,
                    ],
                    'is_active' => true,
                    'is_validTransaction' => $isValid,
                    'is_validRegularExp' => $is_validRegularExp, //it means there was an error in the regex pattern, not just that it didn't match
                ];
                SMSRegularExp::create($smsregular_exps);
            }else{
                $existingExp->update([
                    'transactionType' => $transactionType,
                    'stripNewLines' => $stripNewLines,
                    'data' => [
                        'sample_smsMessage' => $message,
                        'ai_output' => $ai_output,
                        'regular_expMatches' => $matches,
                    ],
                    'is_validTransaction' => $isValid,
                    'is_validRegularExp' => $is_validRegularExp, //it means there was an error in the regex pattern, not just that it didn't match
                ]);
            }
        }
    }
}
