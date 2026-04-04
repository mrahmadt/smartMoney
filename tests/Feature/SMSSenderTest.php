<?php

use App\Models\SMSSender;
use App\Models\SMSRegularExp;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createSender($sender = 'testbank', $is_active = true): int
{
    return DB::table('smssenders')->insertGetId([
        'sender' => $sender,
        'is_active' => $is_active,
    ]);
}

// --- isValidSender ---

test('isValidSender returns true for active sender', function () {
    createSender('mybank', true);

    expect(SMSSender::isValidSender('mybank'))->toBeTrue();
});

test('isValidSender is case-insensitive', function () {
    createSender('mybank', true);

    expect(SMSSender::isValidSender('MYBANK'))->toBeTrue();
    expect(SMSSender::isValidSender('MyBank'))->toBeTrue();
});

test('isValidSender returns false for inactive sender', function () {
    createSender('mybank', false);

    expect(SMSSender::isValidSender('mybank'))->toBeFalse();
});

test('isValidSender returns false for unknown sender', function () {
    createSender('mybank', true);

    expect(SMSSender::isValidSender('otherbank'))->toBeFalse();
});

// --- relationships ---

test('sender has regularExps relationship', function () {
    $senderId = createSender('mybank');
    $sender = SMSSender::find($senderId);

    SMSRegularExp::create([
        'sender_id' => $senderId,
        'transactionType' => 'withdrawal',
        'regularExp' => '/test/',
        'regularExpMD5' => md5('/test/'),
        'createdBy' => 'system',
        'data' => [],
        'is_active' => true,
        'is_validTransaction' => true,
        'is_validRegularExp' => true,
    ]);

    expect($sender->regularExps)->toHaveCount(1);
});

test('activeRegularExps only returns active ones', function () {
    $senderId = createSender('mybank');
    $sender = SMSSender::find($senderId);

    SMSRegularExp::create([
        'sender_id' => $senderId,
        'transactionType' => 'withdrawal',
        'regularExp' => '/active/',
        'regularExpMD5' => md5('/active/'),
        'createdBy' => 'system',
        'data' => [],
        'is_active' => true,
        'is_validTransaction' => true,
        'is_validRegularExp' => true,
    ]);

    SMSRegularExp::create([
        'sender_id' => $senderId,
        'transactionType' => 'deposit',
        'regularExp' => '/inactive/',
        'regularExpMD5' => md5('/inactive/'),
        'createdBy' => 'system',
        'data' => [],
        'is_active' => false,
        'is_validTransaction' => true,
        'is_validRegularExp' => true,
    ]);

    expect($sender->regularExps)->toHaveCount(2);
    expect($sender->activeRegularExps)->toHaveCount(1);
});
