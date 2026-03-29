<?php

use App\Models\Transaction;

// --- cleanName ---

test('cleanName removes branch numbers', function () {
    expect(Transaction::cleanName('ALDREES 239'))->toBe('Aldrees');
    expect(Transaction::cleanName('S150 Tamimi Markets'))->toBe('Tamimi Markets');
    expect(Transaction::cleanName('BURGER KING 214 Q07'))->toBe('Burger King');
});

test('cleanName removes trailing codes', function () {
    expect(Transaction::cleanName('KUDU W0059'))->toBe('Kudu');
    expect(Transaction::cleanName('BURGER KING #5878'))->toBe('Burger King');
    expect(Transaction::cleanName('starbucks-S942'))->toBe('Starbucks');
});

test('cleanName removes CO and EST suffixes', function () {
    expect(Transaction::cleanName('NAHDI MEDICAL CO'))->toBe('Nahdi Medical');
    expect(Transaction::cleanName('sleep house est'))->toBe('Sleep House');
});

test('cleanName replaces asterisk with space', function () {
    expect(Transaction::cleanName('GAB*CINEMA'))->toBe('Gab Cinema');
});

test('cleanName removes PAYPAL prefix', function () {
    expect(Transaction::cleanName('PAYPAL MERCHANT'))->toBe('Merchant');
});

test('cleanName removes trailing dot', function () {
    expect(Transaction::cleanName('UNIVERSAL COLD STORE .'))->toBe('Universal Cold Store');
});

test('cleanName removes long trailing numbers', function () {
    expect(Transaction::cleanName('Nintendo CA1160093092'))->toBe('Nintendo');
});

test('cleanName removes city code suffix', function () {
    expect(Transaction::cleanName('CENTERPOINT 21481 RIY'))->toBe('Centerpoint');
});

test('cleanName returns ucwords for empty result', function () {
    // Very short input that patterns might reduce to empty
    $result = Transaction::cleanName('AB');
    expect(strlen($result))->toBeGreaterThan(0);
});

// --- generateDescription ---

test('generateDescription returns first line', function () {
    $msg = "Purchase at STARBUCKS\nAmount: SAR 45.00";
    expect(Transaction::generateDescription($msg))->toBe('Purchase at STARBUCKS');
});

test('generateDescription removes special characters', function () {
    $msg = "Purchase: SAR-45.00/online";
    $desc = Transaction::generateDescription($msg);
    expect($desc)->not->toContain(':');
    expect($desc)->not->toContain('-');
    expect($desc)->not->toContain('/');
});

test('generateDescription limits length to 50 chars', function () {
    $msg = "This is a very long SMS message that should be truncated because it exceeds the fifty character limit";
    $desc = Transaction::generateDescription($msg);
    expect(strlen($desc))->toBeLessThanOrEqual(50);
});

test('generateDescription handles empty string', function () {
    expect(Transaction::generateDescription(''))->toBe('');
});

// --- normalizeTransactionDateTime ---

test('normalizeTransactionDateTime returns ISO 8601 for valid input', function () {
    $result = Transaction::normalizeTransactionDateTime('2026-02-01T00:00:00+00:00');
    expect($result)->toBe('2026-02-01T00:00:00+00:00');
});

test('normalizeTransactionDateTime returns ISO 8601 for Z timezone', function () {
    $result = Transaction::normalizeTransactionDateTime('2026-02-01T12:30:00Z');
    expect($result)->toBe('2026-02-01T12:30:00Z');
});

test('normalizeTransactionDateTime parses Y-m-d H:i:s format', function () {
    $result = Transaction::normalizeTransactionDateTime('2026-01-15 14:30:00', 'Asia/Riyadh');
    expect($result)->toContain('2026-01-15');
    expect($result)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}/');
});

test('normalizeTransactionDateTime parses d/m/Y format', function () {
    $result = Transaction::normalizeTransactionDateTime('15/01/2026', 'Asia/Riyadh');
    expect($result)->toContain('2026');
    expect($result)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}/');
});

test('normalizeTransactionDateTime returns now for empty string', function () {
    $result = Transaction::normalizeTransactionDateTime('', 'Asia/Riyadh');
    expect($result)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}/');
});

test('normalizeTransactionDateTime returns now for null', function () {
    $result = Transaction::normalizeTransactionDateTime(null, 'Asia/Riyadh');
    expect($result)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}/');
});

test('normalizeTransactionDateTime returns now for unparseable string', function () {
    $result = Transaction::normalizeTransactionDateTime('not-a-date', 'Asia/Riyadh');
    expect($result)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}/');
});
