<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AnthropicService implements LLMServiceInterface
{
    private ?string $apiKey;
    private string $endpoint;
    private string $model;
    private array $availableModels = [
        'claude-3-5-sonnet-20241022',
        'claude-3-5-haiku-20241022',
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
        'claude-3-haiku-20240307'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->endpoint = config('services.anthropic.endpoint', 'https://api.anthropic.com/v1');
        $this->model = config('services.anthropic.default_model', 'claude-3-5-sonnet-20241022');
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            // Convert OpenAI format messages to Anthropic format
            $anthropicMessages = $this->convertMessages($messages);
            
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])->timeout(120)->post($this->endpoint . '/messages', [
                'model' => $this->model,
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.7,
                'messages' => $anthropicMessages,
            ]);

            if (!$response->successful()) {
                Log::error('Anthropic API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Anthropic API request failed: ' . $response->body());
            }

            $data = $response->json();
            return $data['content'][0]['text'] ?? '';

        } catch (Exception $e) {
            Log::error('Anthropic service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function convertMessages(array $messages): array
    {
        $converted = [];
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // Anthropic handles system messages differently
                continue;
            }
            
            $converted[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        return $converted;
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
            throw new Exception("Model {$model} is not available for Anthropic service");
        }
        
        $this->model = $model;
        return $this;
    }

    public function getAvailableModels(): array
    {
        return $this->availableModels;
    }
}
