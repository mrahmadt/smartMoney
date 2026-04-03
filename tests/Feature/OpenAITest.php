<?php

use App\Services\OpenAI;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    config([
        'openai.api_key' => 'test-key',
        'openai.organization' => null,
        'openai.project' => null,
        'openai.base_url' => 'https://api.openai.com/v1',
        'openai.default_model' => 'gpt-5-nano',
    ]);
});

function mockOpenAI(array $responses): OpenAI
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);
    return new OpenAI($client);
}

// --- buildPayload ---

test('buildPayload returns basic payload without schema', function () {
    $ai = new OpenAI();
    $payload = $ai->buildPayload('Hello', 'gpt-5-nano');

    expect($payload)->toBe([
        'model' => 'gpt-5-nano',
        'input' => 'Hello',
        'stream' => false,
        'store' => false,
    ]);
});

test('buildPayload includes schema from array', function () {
    $ai = new OpenAI();
    $schema = ['type' => 'json_schema', 'json_schema' => ['name' => 'test']];
    $payload = $ai->buildPayload('Query', 'gpt-5-nano', ['schema' => $schema]);

    expect($payload['text']['format'])->toBe($schema);
    expect($payload)->not->toHaveKey('schema');
});

test('buildPayload parses schema from JSON string', function () {
    $ai = new OpenAI();
    $schemaArray = ['type' => 'json_schema', 'json_schema' => ['name' => 'test']];
    $payload = $ai->buildPayload('Query', 'gpt-5-nano', ['schema' => json_encode($schemaArray)]);

    expect($payload['text']['format'])->toBe($schemaArray);
});

test('buildPayload adds default type to schema without type', function () {
    $ai = new OpenAI();
    $schema = ['json_schema' => ['name' => 'test']];
    $payload = $ai->buildPayload('Query', 'gpt-5-nano', ['schema' => $schema]);

    expect($payload['text']['format']['type'])->toBe('json_schema');
});

test('buildPayload throws on invalid JSON schema string', function () {
    $ai = new OpenAI();
    $ai->buildPayload('Query', 'gpt-5-nano', ['schema' => '{invalid json']);
})->throws(\InvalidArgumentException::class);

test('buildPayload merges extra llmOptions into payload', function () {
    $ai = new OpenAI();
    $payload = $ai->buildPayload('Query', 'gpt-5-nano', ['temperature' => 0.5]);

    expect($payload['temperature'])->toBe(0.5);
    expect($payload['model'])->toBe('gpt-5-nano');
});

// --- runSync ---

test('runSync returns text on successful completed response', function () {
    $body = json_encode([
        'status' => 'completed',
        'output' => [
            ['content' => [['text' => 'Hello world']]],
        ],
    ]);

    $ai = mockOpenAI([new Response(200, [], $body)]);
    $result = $ai->runSync(['query' => 'Say hello']);

    expect($result)->toBe('Hello world');
});

test('runSync returns false when status is not completed', function () {
    $body = json_encode([
        'status' => 'failed',
        'output' => [],
    ]);

    $ai = mockOpenAI([new Response(200, [], $body)]);
    $result = $ai->runSync(['query' => 'Say hello']);

    expect($result)->toBeFalse();
});

test('runSync returns false when output has no text', function () {
    $body = json_encode([
        'status' => 'completed',
        'output' => [
            ['content' => []],
        ],
    ]);

    $ai = mockOpenAI([new Response(200, [], $body)]);
    $result = $ai->runSync(['query' => 'Say hello']);

    expect($result)->toBeFalse();
});

test('runSync throws when query is missing', function () {
    $ai = new OpenAI();
    $ai->runSync([]);
})->throws(\Exception::class, 'Missing required parameter: query');

test('runSync uses default model when not specified', function () {
    $body = json_encode([
        'status' => 'completed',
        'output' => [
            ['content' => [['text' => 'ok']]],
        ],
    ]);

    $ai = mockOpenAI([new Response(200, [], $body)]);
    $result = $ai->runSync(['query' => 'test']);

    expect($result)->toBe('ok');
});

test('runSync returns last output item text', function () {
    $body = json_encode([
        'status' => 'completed',
        'output' => [
            ['content' => [['text' => 'first']]],
            ['content' => [['text' => 'second']]],
        ],
    ]);

    $ai = mockOpenAI([new Response(200, [], $body)]);
    $result = $ai->runSync(['query' => 'test']);

    expect($result)->toBe('second');
});
