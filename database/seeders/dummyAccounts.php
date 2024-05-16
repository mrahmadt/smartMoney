<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Helpers\fireflyIII;

class dummyAccounts extends Seeder
{
    public $minBalance = 1000;
    public $maxBalance = 30000;
    public $minAccounts = 5;
    public $maxAccounts = 10;

    public function run(): void
    {
        $type = 'asset'; // asset, expense, import, revenue, cash, liability, liabilities, initial-balance, reconciliation

        $account_role = ['asset'=>['defaultAsset', 'sharedAsset', 'savingAsset', 'ccAsset', 'cashWalletAsset']];

        $fireflyIII = new fireflyIII();
        $totalAccounts = mt_rand($this->minAccounts, $this->maxAccounts);
        
        for($i=0; $i<$totalAccounts; $i++){
            $options = [
                'name' => fake()->words(3, true),
                'type' => $type,
                'account_number' => fake()->creditCardNumber(),
                'opening_balance' => mt_rand($this->minBalance, $this->maxBalance),
                'opening_balance_date' => date('Y-m-d'),
                // random from string array
                'account_role' => $account_role[$type][array_rand($account_role[$type])],
            ];
            $data = $fireflyIII->createAccount($options);
        }
    }
}
