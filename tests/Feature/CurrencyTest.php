<?php

use App\Models\Currency;

// --- exchangeRate ---

test('exchangeRate method exists', function () {
    expect(method_exists(Currency::class, 'exchangeRate'))->toBeTrue();
});

test('exchangeRate accepts amount from and to parameters', function () {
    $reflection = new ReflectionMethod(Currency::class, 'exchangeRate');
    $params = $reflection->getParameters();

    expect($params)->toHaveCount(3);
    expect($params[0]->getName())->toBe('amount');
    expect($params[1]->getName())->toBe('from');
    expect($params[2]->getName())->toBe('to');
});

test('exchangeRate is a static method', function () {
    $reflection = new ReflectionMethod(Currency::class, 'exchangeRate');
    expect($reflection->isStatic())->toBeTrue();
});

test('Currency model has no database table', function () {
    $currency = new Currency();
    expect($currency->getTable())->toBeFalse();
});
