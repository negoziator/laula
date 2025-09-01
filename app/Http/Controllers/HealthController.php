<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Services\LLM\LLMFactory;
use App\Services\Agents\AgentFactory;
use App\Services\Aula\AulaClient;
use Exception;

class HealthController extends Controller
{
    public function __construct(
        private LLMFactory $llmFactory,
        private AgentFactory $agentFactory,
        private AulaClient $aulaClient
    ) {}

    /**
     * Health check endpoint for the Aula AI system
     */
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => []
        ];

        // Check database
        try {
            \DB::connection()->getPdo();
            $status['services']['database'] = [
                'status' => 'healthy',
                'tables' => [
                    'conversations' => \Schema::hasTable('conversations'),
                    'messages' => \Schema::hasTable('messages')
                ]
            ];
        } catch (Exception $e) {
            $status['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $status['status'] = 'degraded';
        }

        // Check LLM providers
        try {
            $models = $this->llmFactory->getAvailableModels();
            $status['services']['llm'] = [
                'status' => empty($models) ? 'degraded' : 'healthy',
                'available_models' => count($models),
                'models' => array_column($models, 'display_name')
            ];
            
            if (empty($models)) {
                $status['status'] = 'degraded';
            }
        } catch (Exception $e) {
            $status['services']['llm'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $status['status'] = 'degraded';
        }

        // Check agents
        try {
            $agents = $this->agentFactory->getAvailableAgents();
            $status['services']['agents'] = [
                'status' => 'healthy',
                'available_agents' => array_column($agents, 'name')
            ];
        } catch (Exception $e) {
            $status['services']['agents'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $status['status'] = 'degraded';
        }

        // Check Aula client
        $status['services']['aula'] = [
            'status' => $this->aulaClient->isConfigured() ? 'configured' : 'not_configured',
            'configured' => $this->aulaClient->isConfigured()
        ];

        return response()->json($status);
    }
}
