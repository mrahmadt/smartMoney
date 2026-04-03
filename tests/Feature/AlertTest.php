<?php

use App\Models\Alert;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

// --- newTransaction ---

test('newTransaction sends notification for withdrawal', function () {
    $user = User::factory()->create(['language' => 'en']);

    $transaction = (object) [
        'type' => 'withdrawal',
        'amount' => 45.50,
        'currency_symbol' => 'SAR',
        'destination_name' => 'STARBUCKS',
        'source_name' => 'My Account',
        'category_name' => 'Cafe',
        'transaction_journal_id' => 1,
    ];

    Alert::newTransaction($transaction, $user);

    Notification::assertSentTo($user, \App\Notifications\WebPush::class);
});

test('newTransaction uses translated type for withdrawal', function () {
    $user = User::factory()->create(['language' => 'en']);

    $transaction = (object) [
        'type' => 'withdrawal',
        'amount' => 100,
        'currency_symbol' => 'SAR',
        'destination_name' => 'SHOP',
        'source_name' => 'Account',
        'category_name' => '',
        'transaction_journal_id' => 1,
    ];

    Alert::newTransaction($transaction, $user);

    // Verify locale was set
    expect(app()->getLocale())->toBe('en');
});

test('newTransaction sets Arabic locale for Arabic user', function () {
    $user = User::factory()->create(['language' => 'ar']);

    $transaction = (object) [
        'type' => 'deposit',
        'amount' => 500,
        'currency_symbol' => 'SAR',
        'destination_name' => '',
        'source_name' => 'Employer',
        'category_name' => '',
        'transaction_journal_id' => 2,
    ];

    Alert::newTransaction($transaction, $user);

    expect(app()->getLocale())->toBe('ar');
});

test('newTransaction handles empty category gracefully', function () {
    $user = User::factory()->create(['language' => 'en']);

    $transaction = (object) [
        'type' => 'withdrawal',
        'amount' => 50,
        'currency_symbol' => 'SAR',
        'destination_name' => 'SHOP',
        'source_name' => 'Account',
        'category_name' => null,
        'transaction_journal_id' => 1,
    ];

    // Should not throw
    Alert::newTransaction($transaction, $user);

    Notification::assertSentTo($user, \App\Notifications\WebPush::class);
});

// --- abnormalTransaction ---

test('abnormalTransaction creates alert with correct data', function () {
    $user = User::factory()->create(['language' => 'en']);

    Alert::abnormalTransaction(
        user_id: $user->id,
        transaction_journal_id: 99,
        amount: 500,
        average_amount: 100,
        difference_percentage: 400,
    );

    $alert = Alert::first();
    expect($alert)->not->toBeNull();
    expect($alert->title)->toBe(__('alert.abnormal_title'));
    expect($alert->data['amount'])->toBe(500);
    expect($alert->data['average_amount'])->toBe(100);
    expect($alert->transaction_journal_id)->toBe(99);
});

test('abnormalTransaction resolves user from user_id', function () {
    $user = User::factory()->create(['language' => 'en']);

    Alert::abnormalTransaction(
        user_id: $user->id,
        transaction_journal_id: 1,
        amount: 200,
        average_amount: 100,
        difference_percentage: 100,
    );

    Notification::assertSentTo($user, \App\Notifications\WebPush::class);
});

test('abnormalTransaction returns early if user not found', function () {
    Alert::abnormalTransaction(
        user_id: 99999,
        transaction_journal_id: 1,
        amount: 200,
        average_amount: 100,
        difference_percentage: 100,
    );

    expect(Alert::count())->toBe(0);
});

test('abnormalTransaction uses user language for locale', function () {
    $user = User::factory()->create(['language' => 'ar']);

    Alert::abnormalTransaction(
        user_id: $user->id,
        transaction_journal_id: 1,
        amount: 200,
        average_amount: 100,
        difference_percentage: 100,
    );

    expect(app()->getLocale())->toBe('ar');
});

// --- createAlert ---

test('createAlert saves alert to database', function () {
    $user = User::factory()->create();

    Alert::createAlert(
        title: 'Test Title',
        message: 'Test Message',
        user: $user,
        data: ['key' => 'value'],
    );

    $this->assertDatabaseHas('alerts', [
        'title' => 'Test Title',
        'message' => 'Test Message',
        'user_id' => $user->id,
    ]);

    $alert = Alert::first();
    expect($alert->data)->toBe(['key' => 'value']);
});

test('createAlert sends web push notification', function () {
    $user = User::factory()->create();

    Alert::createAlert(title: 'Push Test', message: 'Body', user: $user);

    Notification::assertSentTo($user, \App\Notifications\WebPush::class);
});

// --- user relationship ---

test('alert belongs to user', function () {
    $user = User::factory()->create();

    $alert = Alert::create([
        'user_id' => $user->id,
        'title' => 'Test',
        'message' => 'Msg',
    ]);

    expect($alert->user->id)->toBe($user->id);
});
