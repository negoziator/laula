<?php

namespace App\Services\Agents;

use App\Models\Conversation;

interface AgentInterface
{
    /**
     * Process a user query and return a response
     */
    public function processQuery(string $query, Conversation $conversation): string;

    /**
     * Get the agent type identifier
     */
    public function getType(): string;

    /**
     * Get the system prompt for this agent
     */
    public function getSystemPrompt(): string;

    /**
     * Get available tools for this agent
     */
    public function getAvailableTools(): array;
}
