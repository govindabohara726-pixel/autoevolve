<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiClient
{
    public function configured(): bool
    {
        return filled(config('services.ai.key'));
    }

    public function json(array $messages, float $temperature = 0.3): array
    {
        $key = config('services.ai.key');
        if (!$key) throw new RuntimeException('AI_API_KEY is not configured on the Laravel backend.');

        $response = Http::timeout(90)
            ->acceptJson()
            ->withToken($key)
            ->post(config('services.ai.base_url').'/chat/completions', [
                'model' => config('services.ai.model'),
                'messages' => $messages,
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('AI provider error '.$response->status().': '.mb_substr($response->body(), 0, 500));
        }

        $raw = (string) data_get($response->json(), 'choices.0.message.content', '{}');
        $raw = preg_replace('/^```json\s*|```$/i', '', trim($raw));
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('AI provider returned invalid JSON.');
        return $decoded;
    }
}
