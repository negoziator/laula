<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LLM\LLMFactory;
use App\Services\Agents\AgentFactory;
use App\Services\Aula\AulaClient;
use Exception;

class TestAulaAI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aula:test {--agent=research_agent} {--model=gpt-4o} {--query=Hello, can you help me?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Aula AI system configuration and functionality';

    /**
     * Execute the console command.
     */
    public function handle(LLMFactory $llmFactory, AgentFactory $agentFactory, AulaClient $aulaClient)
    {
        $this->info('🚀 Testing Aula AI System Configuration...');
        $this->newLine();

        // Test database connection
        $this->testDatabase();

        // Test LLM providers
        $this->testLLMProviders($llmFactory);

        // Test agents
        $this->testAgents($agentFactory);

        // Test Aula client (if configured)
        $this->testAulaClient($aulaClient);

        // Run a sample chat if requested
        if ($this->option('query') !== 'Hello, can you help me?' || $this->confirm('Would you like to test a sample chat?')) {
            $this->testSampleChat($agentFactory);
        }

        $this->newLine();
        $this->info('✅ Aula AI system test completed!');
    }

    private function testDatabase(): void
    {
        $this->info('📊 Testing database connection...');
        
        try {
            \DB::connection()->getPdo();
            $this->line('   ✅ Database connection: OK');
            
            // Check if migrations are run
            if (\Schema::hasTable('conversations') && \Schema::hasTable('messages')) {
                $this->line('   ✅ Database tables: OK');
            } else {
                $this->warn('   ⚠️  Database tables missing. Run: php artisan migrate');
            }
        } catch (Exception $e) {
            $this->error('   ❌ Database connection failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testLLMProviders(LLMFactory $llmFactory): void
    {
        $this->info('🤖 Testing LLM providers...');
        
        $models = $llmFactory->getAvailableModels();
        
        if (empty($models)) {
            $this->warn('   ⚠️  No LLM providers configured. Add API keys to .env file.');
        } else {
            $this->line('   ✅ Available models:');
            foreach ($models as $model) {
                $this->line('      - ' . $model['display_name'] . ' (' . $model['provider'] . ')');
            }
        }
        
        $this->newLine();
    }

    private function testAgents(AgentFactory $agentFactory): void
    {
        $this->info('🎯 Testing agents...');
        
        $agents = $agentFactory->getAvailableAgents();
        
        foreach ($agents as $agentInfo) {
            try {
                $agent = $agentFactory->create($agentInfo['type']);
                $this->line('   ✅ ' . $agentInfo['name'] . ': OK');
            } catch (Exception $e) {
                $this->error('   ❌ ' . $agentInfo['name'] . ': ' . $e->getMessage());
            }
        }
        
        $this->newLine();
    }

    private function testAulaClient(AulaClient $aulaClient): void
    {
        $this->info('🏫 Testing Aula client...');
        
        if ($aulaClient->isConfigured()) {
            $this->line('   ✅ Aula credentials configured');
            $this->warn('   ℹ️  Note: Aula login testing requires valid Danish school credentials');
        } else {
            $this->warn('   ⚠️  Aula credentials not configured (optional)');
        }
        
        $this->newLine();
    }

    private function testSampleChat(AgentFactory $agentFactory): void
    {
        $this->info('💬 Testing sample chat...');
        
        $agentType = $this->option('agent');
        $model = $this->option('model');
        $query = $this->option('query');
        
        try {
            // Create a test conversation
            $conversation = \App\Models\Conversation::create([
                'agent_type' => $agentType,
                'model' => $model,
                'title' => 'Test Conversation'
            ]);
            
            $agent = $agentFactory->create($agentType);
            
            $this->line('   🤖 Agent: ' . $agentType);
            $this->line('   🧠 Model: ' . $model);
            $this->line('   💭 Query: ' . $query);
            $this->newLine();
            
            $this->info('   Processing...');
            $response = $agent->processQuery($query, $conversation);
            
            $this->line('   📝 Response: ' . \Str::limit($response, 200));
            
            // Clean up test conversation
            $conversation->delete();
            
        } catch (Exception $e) {
            $this->error('   ❌ Chat test failed: ' . $e->getMessage());
        }
    }
}
