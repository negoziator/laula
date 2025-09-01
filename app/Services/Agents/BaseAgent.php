<?php

namespace App\Services\Agents;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\LLM\LLMFactory;
use App\Services\LLM\LLMServiceInterface;
use Carbon\Carbon;

abstract class BaseAgent implements AgentInterface
{
    protected LLMServiceInterface $llmService;
    protected LLMFactory $llmFactory;

    public function __construct(LLMFactory $llmFactory)
    {
        $this->llmFactory = $llmFactory;
    }

    public function processQuery(string $query, Conversation $conversation): string
    {
        // Initialize LLM service for this conversation
        $this->llmService = $this->llmFactory->create($conversation->model);

        // Build message history
        $messages = $this->buildMessageHistory($conversation, $query);

        // Process with tools if needed
        $response = $this->processWithTools($messages, $conversation);

        // Store the user message and AI response
        $this->storeMessages($conversation, $query, $response);

        return $response;
    }

    protected function buildMessageHistory(Conversation $conversation, string $newQuery): array
    {
        $messages = [];

        // Add system prompt
        $messages[] = [
            'role' => 'system',
            'content' => $this->getSystemPrompt()
        ];

        // Add conversation history
        $previousMessages = $conversation->messages()
            ->orderBy('sent_at')
            ->limit(20) // Limit to last 20 messages to avoid token limits
            ->get();

        foreach ($previousMessages as $message) {
            $messages[] = [
                'role' => $message->is_user ? 'user' : 'assistant',
                'content' => $message->content
            ];
        }

        // Add new user query
        $messages[] = [
            'role' => 'user',
            'content' => $newQuery
        ];

        return $messages;
    }

    protected function processWithTools(array $messages, Conversation $conversation): string
    {
        // Base implementation - just call LLM without tools
        return $this->llmService->chat($messages);
    }

    protected function storeMessages(Conversation $conversation, string $userQuery, string $aiResponse): void
    {
        $now = Carbon::now();

        // Store user message
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => $userQuery,
            'is_user' => true,
            'role' => 'user',
            'sent_at' => $now,
        ]);

        // Store AI response
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => $aiResponse,
            'is_user' => false,
            'role' => 'assistant',
            'sent_at' => $now->addSecond(),
        ]);

        // Update conversation title if it's the first exchange
        if ($conversation->messages()->count() <= 2 && empty($conversation->title)) {
            $title = $this->generateConversationTitle($userQuery);
            $conversation->update(['title' => $title]);
        }
    }

    protected function generateConversationTitle(string $firstMessage): string
    {
        // Simple title generation - could be enhanced with LLM
        $title = substr($firstMessage, 0, 50);
        if (strlen($firstMessage) > 50) {
            $title .= '...';
        }
        return $title;
    }

    abstract public function getType(): string;
    abstract public function getSystemPrompt(): string;
    abstract public function getAvailableTools(): array;
}
