<?php

namespace App\Services\LLM;

interface LLMServiceInterface
{
    /**
     * Send a message to the LLM and get a response
     */
    public function chat(array $messages, array $options = []): string;

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool;

    /**
     * Get the model name
     */
    public function getModel(): string;

    /**
     * Set the model to use
     */
    public function setModel(string $model): self;

    /**
     * Get available models for this provider
     */
    public function getAvailableModels(): array;
}
