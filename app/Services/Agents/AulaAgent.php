<?php

namespace App\Services\Agents;

use App\Models\Conversation;
use App\Services\LLM\LLMFactory;
use App\Services\Aula\AulaClient;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class AulaAgent extends BaseAgent
{
    private AulaClient $aulaClient;

    public function __construct(LLMFactory $llmFactory, AulaClient $aulaClient)
    {
        parent::__construct($llmFactory);
        $this->aulaClient = $aulaClient;
    }

    public function getType(): string
    {
        return 'aula_agent';
    }

    public function getSystemPrompt(): string
    {
        $currentTime = Carbon::now()->toISOString();
        
        return "current_time: {$currentTime}
You're a helpful research assistant. You're an expert in navigating the Danish school communication system, Aula.
Only use the tools if the user is talking about the school, institution or about their kids.
Make sure to set the active child before using any of the tools (except for fetch_basic_data).

Available tools:
- set_active_child: Set which child profile we're operating on. Expects a single string argument: the child's name.
- fetch_basic_data: Return some basic info on all children's {name: institution}.
- fetch_daily_overview: Return today's presence overview for the active child. Requires active child to be set.
- fetch_messages: Fetch the latest unread message for the active child. Requires active child to be set.
- fetch_calendar: Fetch upcoming calendar events for the next N days. Expects an integer argument. Requires active child to be set.";
    }

    public function getAvailableTools(): array
    {
        return [
            'set_active_child' => 'Set which child profile we\'re operating on',
            'fetch_basic_data' => 'Return basic info on all children',
            'fetch_daily_overview' => 'Return today\'s presence overview for the active child',
            'fetch_messages' => 'Fetch the latest messages for the active child',
            'fetch_calendar' => 'Fetch upcoming calendar events for the next N days'
        ];
    }

    protected function processWithTools(array $messages, Conversation $conversation): string
    {
        if (!$this->aulaClient->isConfigured()) {
            return "Aula integration is not configured. Please check your Aula credentials in the settings.";
        }

        // Enhanced processing with Aula tool support
        $maxIterations = 5;
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $response = $this->llmService->chat($messages);
            
            // Check if the response contains tool calls
            $toolCalls = $this->extractToolCalls($response);
            
            if (empty($toolCalls)) {
                // No more tool calls needed, return the response
                return $response;
            }
            
            // Execute tool calls and add results to conversation
            foreach ($toolCalls as $toolCall) {
                $toolResult = $this->executeTool($toolCall);
                
                // Add tool call and result to messages
                $messages[] = [
                    'role' => 'assistant',
                    'content' => "I'll use the {$toolCall['name']} tool: {$toolCall['description']}"
                ];
                
                $messages[] = [
                    'role' => 'user',
                    'content' => "Tool result: " . $toolResult
                ];
            }
            
            $iteration++;
        }
        
        // If we've reached max iterations, get final response
        return $this->llmService->chat($messages);
    }

    private function extractToolCalls(string $response): array
    {
        $toolCalls = [];
        
        // Pattern matching for Aula tool calls
        if (preg_match('/set_active_child.*?["\']([^"\']+)["\']/', $response, $matches)) {
            $toolCalls[] = [
                'name' => 'set_active_child',
                'description' => "Set active child to: {$matches[1]}",
                'parameters' => [
                    'name' => $matches[1]
                ]
            ];
        }
        
        if (preg_match('/fetch_basic_data/', $response)) {
            $toolCalls[] = [
                'name' => 'fetch_basic_data',
                'description' => "Fetch basic data for all children",
                'parameters' => []
            ];
        }
        
        if (preg_match('/fetch_daily_overview/', $response)) {
            $toolCalls[] = [
                'name' => 'fetch_daily_overview',
                'description' => "Fetch daily overview for active child",
                'parameters' => []
            ];
        }
        
        if (preg_match('/fetch_messages/', $response)) {
            $toolCalls[] = [
                'name' => 'fetch_messages',
                'description' => "Fetch messages for active child",
                'parameters' => []
            ];
        }
        
        if (preg_match('/fetch_calendar.*?(\d+)/', $response, $matches)) {
            $toolCalls[] = [
                'name' => 'fetch_calendar',
                'description' => "Fetch calendar for next {$matches[1]} days",
                'parameters' => [
                    'days' => (int)$matches[1]
                ]
            ];
        } elseif (preg_match('/fetch_calendar/', $response)) {
            $toolCalls[] = [
                'name' => 'fetch_calendar',
                'description' => "Fetch calendar for next 14 days",
                'parameters' => [
                    'days' => 14
                ]
            ];
        }
        
        return $toolCalls;
    }

    private function executeTool(array $toolCall): string
    {
        try {
            switch ($toolCall['name']) {
                case 'set_active_child':
                    $this->aulaClient->setActiveChild($toolCall['parameters']['name']);
                    return "Active child set to: {$toolCall['parameters']['name']}";
                    
                case 'fetch_basic_data':
                    $data = $this->aulaClient->fetchBasicData();
                    return "Children data: " . json_encode($data, JSON_PRETTY_PRINT);
                    
                case 'fetch_daily_overview':
                    $data = $this->aulaClient->fetchDailyOverview();
                    return "Daily overview: " . json_encode($data, JSON_PRETTY_PRINT);
                    
                case 'fetch_messages':
                    $data = $this->aulaClient->fetchMessages();
                    return "Messages: " . json_encode($data, JSON_PRETTY_PRINT);
                    
                case 'fetch_calendar':
                    $days = $toolCall['parameters']['days'] ?? 14;
                    $data = $this->aulaClient->fetchCalendar($days);
                    return "Calendar events for next {$days} days: " . json_encode($data, JSON_PRETTY_PRINT);
                    
                default:
                    return "Unknown tool: {$toolCall['name']}";
            }
        } catch (Exception $e) {
            Log::error("Aula tool execution error", [
                'tool' => $toolCall['name'],
                'error' => $e->getMessage()
            ]);
            return "Error executing tool: " . $e->getMessage();
        }
    }
}
