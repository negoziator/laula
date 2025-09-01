<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Agents\AgentFactory;
use App\Services\LLM\LLMFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatController extends Controller
{
    private AgentFactory $agentFactory;
    private LLMFactory $llmFactory;

    public function __construct(AgentFactory $agentFactory, LLMFactory $llmFactory)
    {
        $this->agentFactory = $agentFactory;
        $this->llmFactory = $llmFactory;
    }

    /**
     * Send a chat message and get AI response
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:2000',
            'model' => 'required|string',
            'agent' => 'required|string|in:research_agent,aula_agent',
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        try {
            // Get or create conversation
            $conversation = $this->getOrCreateConversation(
                $request->conversation_id,
                $request->agent,
                $request->model
            );

            // Create agent and process query
            $agent = $this->agentFactory->create($request->agent);
            $response = $agent->processQuery($request->query, $conversation);

            return response()->json([
                'success' => true,
                'response' => $response,
                'conversation_id' => $conversation->id,
                'timestamp' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('Chat error', [
                'error' => $e->getMessage(),
                'query' => $request->query,
                'agent' => $request->agent,
                'model' => $request->model
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available models
     */
    public function models(): JsonResponse
    {
        try {
            $models = $this->llmFactory->getAvailableModels();
            return response()->json([
                'success' => true,
                'models' => $models
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available agents
     */
    public function agents(): JsonResponse
    {
        try {
            $agents = $this->agentFactory->getAvailableAgents();
            return response()->json([
                'success' => true,
                'agents' => $agents
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function conversation(Request $request, int $conversationId): JsonResponse
    {
        try {
            $conversation = Conversation::with('messages')->findOrFail($conversationId);
            
            return response()->json([
                'success' => true,
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'agent_type' => $conversation->agent_type,
                    'model' => $conversation->model,
                    'messages' => $conversation->messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'is_user' => $message->is_user,
                            'timestamp' => $message->sent_at->format('H:i'),
                            'sent_at' => $message->sent_at->toISOString()
                        ];
                    })
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get user's conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        try {
            $conversations = Conversation::with('messages')
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($conversation) {
                    return [
                        'id' => $conversation->id,
                        'title' => $conversation->title ?: 'New Conversation',
                        'agent_type' => $conversation->agent_type,
                        'model' => $conversation->model,
                        'last_message' => $conversation->last_message?->content,
                        'updated_at' => $conversation->updated_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'conversations' => $conversations
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getOrCreateConversation(?int $conversationId, string $agentType, string $model): Conversation
    {
        if ($conversationId) {
            return Conversation::findOrFail($conversationId);
        }

        return Conversation::create([
            'agent_type' => $agentType,
            'model' => $model,
            'user_id' => null, // Will be set when authentication is added
        ]);
    }
}
