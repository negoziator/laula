<?php

namespace App\Services\Agents;

use App\Services\LLM\LLMFactory;
use App\Services\Aula\AulaClient;
use Exception;

class AgentFactory
{
    private LLMFactory $llmFactory;
    private AulaClient $aulaClient;

    public function __construct(LLMFactory $llmFactory, AulaClient $aulaClient)
    {
        $this->llmFactory = $llmFactory;
        $this->aulaClient = $aulaClient;
    }

    public function create(string $agentType): AgentInterface
    {
        switch ($agentType) {
            case 'research_agent':
                return new ResearchAgent($this->llmFactory);
                
            case 'aula_agent':
                return new AulaAgent($this->llmFactory, $this->aulaClient);
                
            default:
                throw new Exception("Unknown agent type: {$agentType}");
        }
    }

    public function getAvailableAgents(): array
    {
        return [
            [
                'type' => 'research_agent',
                'name' => 'Research Agent',
                'description' => 'Performs multi-step Google searches and fetches page content for research queries'
            ],
            [
                'type' => 'aula_agent',
                'name' => 'Aula Agent',
                'description' => 'Integrates with the Danish Aula school system to fetch profiles, messages, calendar events, etc.'
            ]
        ];
    }
}
