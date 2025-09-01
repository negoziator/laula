<?php

namespace App\Services\Agents;

use App\Models\Conversation;
use App\Services\LLM\LLMFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ResearchAgent extends BaseAgent
{
    public function __construct(LLMFactory $llmFactory)
    {
        parent::__construct($llmFactory);
    }

    public function getType(): string
    {
        return 'research_agent';
    }

    public function getSystemPrompt(): string
    {
        $currentTime = Carbon::now()->toISOString();
        
        return "current_time: {$currentTime}
You're a helpful research assistant, you are an expert in research. 
If you are given a question you write strong keywords to do 3-5 searches in total 
(each with a query_number) and then combine the results. If some of the results seem relevant, 
use the fetch_url tool to get the full content of the page.

Available tools:
- google_search: Look up 3–5 results on Google
- fetch_url: Fetch and return the plain‐text of any URL";
    }

    public function getAvailableTools(): array
    {
        return [
            'google_search' => 'Look up 3–5 results on Google',
            'fetch_url' => 'Fetch and return the plain‐text of any URL'
        ];
    }

    protected function processWithTools(array $messages, Conversation $conversation): string
    {
        // Enhanced processing with tool support
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
        
        // Simple pattern matching for tool calls
        // In a production system, you'd want more sophisticated parsing
        if (preg_match('/google_search.*?query[:\s]*["\']([^"\']+)["\'].*?query_number[:\s]*(\d+)/i', $response, $matches)) {
            $toolCalls[] = [
                'name' => 'google_search',
                'description' => "Search for: {$matches[1]}",
                'parameters' => [
                    'query' => $matches[1],
                    'query_number' => (int)$matches[2]
                ]
            ];
        }
        
        if (preg_match('/fetch_url.*?url[:\s]*["\']([^"\']+)["\']/', $response, $matches)) {
            $toolCalls[] = [
                'name' => 'fetch_url',
                'description' => "Fetch content from: {$matches[1]}",
                'parameters' => [
                    'url' => $matches[1]
                ]
            ];
        }
        
        return $toolCalls;
    }

    private function executeTool(array $toolCall): string
    {
        try {
            switch ($toolCall['name']) {
                case 'google_search':
                    return $this->googleSearch($toolCall['parameters']['query'], $toolCall['parameters']['query_number'] ?? 1);
                    
                case 'fetch_url':
                    return $this->fetchUrl($toolCall['parameters']['url']);
                    
                default:
                    return "Unknown tool: {$toolCall['name']}";
            }
        } catch (Exception $e) {
            Log::error("Tool execution error", [
                'tool' => $toolCall['name'],
                'error' => $e->getMessage()
            ]);
            return "Error executing tool: " . $e->getMessage();
        }
    }

    private function googleSearch(string $query, int $queryNumber): string
    {
        $apiKey = config('services.google.search_api_key');
        $searchEngineId = config('services.google.search_engine_id');
        
        if (!$apiKey || !$searchEngineId) {
            return "Google Search API not configured";
        }

        try {
            $response = Http::get('https://customsearch.googleapis.com/customsearch/v1', [
                'key' => $apiKey,
                'cx' => $searchEngineId,
                'q' => $query,
                'num' => 5,
                'cr' => 'countryDK'
            ]);

            if (!$response->successful()) {
                return "Search failed: " . $response->body();
            }

            $data = $response->json();
            $results = [];

            foreach ($data['items'] ?? [] as $item) {
                $results[] = [
                    'title' => $item['title'] ?? '',
                    'link' => $item['link'] ?? '',
                    'snippet' => $item['snippet'] ?? ''
                ];
            }

            return "Search query {$queryNumber}: {$query}\nResults:\n" . 
                   collect($results)->map(function ($result) {
                       return "- {$result['title']}\n  {$result['link']}\n  {$result['snippet']}\n";
                   })->join("\n");

        } catch (Exception $e) {
            return "Search error: " . $e->getMessage();
        }
    }

    private function fetchUrl(string $url): string
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                return "Failed to fetch URL: " . $response->status();
            }

            // Simple text extraction - in production you'd want better HTML parsing
            $content = $response->body();
            $content = strip_tags($content);
            $content = preg_replace('/\s+/', ' ', $content);
            
            // Limit content length to avoid token limits
            if (strlen($content) > 5000) {
                $content = substr($content, 0, 5000) . '...';
            }

            return "Content from {$url}:\n" . trim($content);

        } catch (Exception $e) {
            return "Error fetching URL: " . $e->getMessage();
        }
    }
}
