<?php

namespace App\Services\LLM;

use Exception;

class LLMFactory
{
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            'openai' => OpenAIService::class,
            'anthropic' => AnthropicService::class,
        ];
    }

    public function create(string $model): LLMServiceInterface
    {
        $provider = $this->getProviderForModel($model);
        
        if (!$provider) {
            throw new Exception("No provider found for model: {$model}");
        }

        $service = app($this->providers[$provider]);
        $service->setModel($model);

        if (!$service->isAvailable()) {
            throw new Exception("Provider {$provider} is not available (check API keys)");
        }

        return $service;
    }

    public function getAvailableModels(): array
    {
        $models = [];
        
        foreach ($this->providers as $providerName => $providerClass) {
            $service = app($providerClass);
            if ($service->isAvailable()) {
                $providerModels = $service->getAvailableModels();
                foreach ($providerModels as $model) {
                    $models[] = [
                        'model' => $model,
                        'provider' => $providerName,
                        'display_name' => $this->getDisplayName($model, $providerName)
                    ];
                }
            }
        }

        return $models;
    }

    private function getProviderForModel(string $model): ?string
    {
        // Handle anthropic: prefix from original project
        if (str_starts_with($model, 'anthropic:')) {
            return 'anthropic';
        }

        // Check each provider's available models
        foreach ($this->providers as $providerName => $providerClass) {
            $service = app($providerClass);
            if (in_array($model, $service->getAvailableModels())) {
                return $providerName;
            }
        }

        return null;
    }

    private function getDisplayName(string $model, string $provider): string
    {
        $displayNames = [
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
        ];

        return $displayNames[$model] ?? ucfirst($provider) . ': ' . $model;
    }
}
