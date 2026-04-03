<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\fireflyIII;

class Currency extends Model
{
    use HasFactory;
    protected $table = false;


    public static function exchangeRate($amount, $from, $to)
    {
        $fireflyIII = new fireflyIII();
        $exchangeRate = $fireflyIII->getExchangeRate($from, $to);
        if(!$exchangeRate) {
            return false;
        }

        if(!isset($exchangeRate->data[0]->type)) {
            return false;
        }
        $exchangeRate = $exchangeRate->data[0];

        if (!isset($exchangeRate->attributes->rate)) {
            return false;
        }

        if (!isset($exchangeRate->attributes->from_currency_code)) {
            return false;
        }

        if (!isset($exchangeRate->attributes->to_currency_code)) {
            return false;
        }

        $rate = (float) $exchangeRate->attributes->rate;

        if ($rate <= 0) {
            return false;
        }

        // Make sure the returned rate matches what we asked for
        $rateFrom = strtoupper(trim($exchangeRate->attributes->from_currency_code));
        $rateTo   = strtoupper(trim($exchangeRate->attributes->to_currency_code));

        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));

        // If Firefly returned a reversed pair, invert the rate
        if ($rateFrom === $to && $rateTo === $from) {
            $rate = 1 / $rate;
        } elseif (!($rateFrom === $from && $rateTo === $to)) {
            // Unknown pair
            return false;
        }

        $converted = ((float) $amount) * $rate;

        return $converted;
    }
}
