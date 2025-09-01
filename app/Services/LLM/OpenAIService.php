<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService implements LLMServiceInterface
{
    private ?string $apiKey;
    private string $endpoint;
    private string $model;
    private array $availableModels = [
        'gpt-4o',
        'gpt-4o-mini',
        'gpt-4-turbo',
        'gpt-3.5-turbo'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->endpoint = config('services.openai.endpoint', 'https://api.openai.com/v1');
        $this->model = config('services.openai.default_model', 'gpt-4o');
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->endpoint . '/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'stream' => false,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';

        } catch (Exception $e) {
            Log::error('OpenAI service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        if (!in_array($model, $this->availableModels)) {
            throw new Exception("Model {$model} is not available for OpenAI service");
        }
        
        $this->model = $model;
        return $this;
    }

    public function getAvailableModels(): array
    {
        return $this->availableModels;
    }
}
