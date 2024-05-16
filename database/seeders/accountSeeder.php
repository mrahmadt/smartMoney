<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;

class accountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'FF_account_id' => 1,
                'FF_account_name' => 'SAIB Main',
                'FF_account_type' => 'asset',
                'account_code' => '001',
                'sms_sender' => 'saib',
                'defaultAccount' => true,
            ],
            [
                'FF_account_id' => 1,
                'FF_account_name' => 'SAIB Main',
                'FF_account_type' => 'asset',
                'account_code' => '7001',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 1,
                'FF_account_name' => 'SAIB Main',
                'FF_account_type' => 'asset',
                'account_code' => '6020',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 2,
                'FF_account_name' => 'SAIB savings account',
                'FF_account_type' => 'asset',
                'account_code' => '003',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 2,
                'FF_account_name' => 'SAIB savings account',
                'FF_account_type' => 'asset',
                'account_code' => '7003',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 5,
                'FF_account_name' => 'Visa SAIB CC',
                'FF_account_type' => 'asset',
                'account_code' => '12595790',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 5,
                'FF_account_name' => 'Visa SAIB CC',
                'FF_account_type' => 'asset',
                'account_code' => '3021',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 5,
                'FF_account_name' => 'Visa SAIB CC',
                'FF_account_type' => 'asset',
                'account_code' => '6484',
                'sms_sender' => 'saib',
                'budget_id' => 1,
                'alertPushNotification' => true,
                'user_id' => 2,
            ],
            [
                'FF_account_id' => 5,
                'FF_account_name' => 'Visa SAIB CC',
                'FF_account_type' => 'asset',
                'account_code' => '7660',
                'sms_sender' => 'saib',
                'budget_id' => 1,
                'alertPushNotification' => true,
                'user_id' => 2,
            ],
            [
                'FF_account_id' => 10,
                'FF_account_name' => 'Travel CC',
                'FF_account_type' => 'asset',
                'account_code' => '6314',
                'sms_sender' => 'saib',
            ],
            [
                'FF_account_id' => 12,
                'FF_account_name' => 'Inma Saving',
                'FF_account_type' => 'asset',
                'account_code' => '7001',
                'sms_sender' => 'alinmabank',
            ],
            [
                'FF_account_id' => 12,
                'FF_account_name' => 'AlBilad',
                'FF_account_type' => 'asset',
                'sms_sender' => 'bankalbilad',
                'defaultAccount' => true,
            ],
            [
                'FF_account_id' => 16,
                'FF_account_name' => 'SAB',
                'account_code' => '4001',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
                'defaultAccount' => true,
            ],
            [
                'FF_account_id' => 16,
                'FF_account_name' => 'SAB',
                'account_code' => '001',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
            ],
            [
                'FF_account_id' => 18,
                'FF_account_name' => 'SAB CC',
                'account_code' => '9083',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
                'defaultAccount' => true,
            ],

            [
                'FF_account_id' => 19,
                'FF_account_name' => 'SAB AlFursan CC',
                'account_code' => '5540',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
                'defaultAccount' => true,
            ],
            [
                'FF_account_id' => 19,
                'FF_account_name' => 'SAB AlFursan CC',
                'account_code' => '1511',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
            ],
            [
                'FF_account_id' => 19,
                'FF_account_name' => 'SAB AlFursan CC',
                'account_code' => '1784',
                'FF_account_type' => 'asset',
                'sms_sender' => 'sab',
            ],

            [
                'FF_account_id' => 20,
                'FF_account_name' => 'STC Pay',
                'account_code' => '--STCPAY--',
                'FF_account_type' => 'asset',
                'sms_sender' => 'STCPAY',
                'defaultAccount' => true,
            ],

            [
                'FF_account_id' => 23,
                'FF_account_name' => 'Alahli 1',
                'account_code' => '0101',
                'FF_account_type' => 'asset',
                'sms_sender' => 'snb-alahli',
                'defaultAccount' => true,
            ],

            [
                'FF_account_id' => 23,
                'FF_account_name' => 'Alahli 1',
                'account_code' => '0108',
                'FF_account_type' => 'asset',
                'sms_sender' => 'snb-alahli',
                'defaultAccount' => true,
            ],

            [
                'FF_account_id' => 23,
                'FF_account_name' => 'Alahli 1',
                'account_code' => '1009',
                'FF_account_type' => 'asset',
                'sms_sender' => 'snb-alahli',
                'defaultAccount' => true,
            ],
            [
                'FF_account_id' => 24,
                'FF_account_name' => 'Alahli CC',
                'account_code' => '4432',
                'FF_account_type' => 'asset',
                'sms_sender' => 'snb-alahli',
                'defaultAccount' => true,
            ],
        ];
        foreach($accounts as $key => $values){
                Account::create([
                    'FF_account_id' => $values['FF_account_id'] ?? null,
                    'FF_account_name' => $values['FF_account_name'] ?? null,
                    'account_code' => $values['account_code'] ?? null,
                    'FF_account_type' => $values['FF_account_type'] ?? 'asset',
                    'sms_sender' => $values['sms_sender'] ?? null,
                    'budget_id' => $values['budget_id'] ?? null,
                    'user_id' => $values['user_id'] ?? null,
                    'defaultAccount' => $values['defaultAccount'] ?? false,
                    'alertPushNotification' => $values['alertPushNotification'] ?? false,
                ]);
        }
    }
}
