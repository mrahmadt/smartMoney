<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use App\Models\OpenAIJob;
use App\Models\Promptlog;


class OpenAI
{
    public function __construct(
        private Client $http = new Client()
    ) {}

    private function headers(): array
    {
        $h = [
            'Authorization' => 'Bearer ' . config('openai.api_key'),
            'Content-Type'  => 'application/json',
        ];
        if ($org = config('openai.organization'))     $h['OpenAI-Organization'] = $org;
        if ($prj = config('openai.project')) $h['OpenAI-Project']      = $prj;
        return $h;
    }

    /**
     * Build a standard Deep Research payload.
     * $query: your research instruction (e.g. "Latest consumer tech updates in last 24h...")
     * $limit: number of items to return
     */
    public function buildPayload($query, $model, array $llmOptions = []): array
{
    $payload = [
        'model' => $model,
        'input' => $query,     // keep as string if you want, but messages array is better (see note below)
        'stream' => false,
        'store'  => false,
    ];

    if (isset($llmOptions['schema'])) {
        $format = $llmOptions['schema'];

        // If schema was provided as JSON string, decode it to array
        if (is_string($format)) {
            $decoded = json_decode($format, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new \InvalidArgumentException(
                    "Invalid schema JSON string: " . json_last_error_msg()
                );
            }
            $format = $decoded;
        }

        // Guard: must be an array/object
        if (!is_array($format)) {
            throw new \InvalidArgumentException("Schema format must be an array or valid JSON string.");
        }

        // Normalize: ensure required Structured Outputs fields exist
        // (your string had "type": "json_schema" which is correct)
        if (!isset($format['type'])) {
            $format = array_merge(['type' => 'json_schema'], $format);
        }

        $payload['text'] = [
            'format' => $format,
        ];

        unset($llmOptions['schema']);
    }

    return array_merge($payload, $llmOptions);
}

    /** Synchronous call (wait for result) */
    public function runSync($options)
    {
        if(!isset($options['query'])){
            throw new \Exception('Missing required parameter: query');
        }

        if(!isset($options['model'])){
            $options['model'] = config('openai.default_model');
        }

        $payload = $this->buildPayload($options['query'], $options['model'], $options['llm_options'] ?? []);

        // dd($payload);

        $resp = $this->http->post(config('openai.base_url').'/responses', [
            'headers' => $this->headers(),
            'json'    => $payload,
            'timeout' => 180,
        ]);

        $json = json_decode((string)$resp->getBody(), true);
        $status = $json['status'] ?? null;
        if ($status !== 'completed') return false;

        $text = $json['output'][sizeof($json['output']) - 1]['content'][0]['text'] ?? false;
        if (!$text) return false;
        return $text;
    }



}
