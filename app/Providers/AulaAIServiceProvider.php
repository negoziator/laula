<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LLM\LLMFactory;
use App\Services\LLM\OpenAIService;
use App\Services\LLM\AnthropicService;
use App\Services\Agents\AgentFactory;
use App\Services\Aula\AulaClient;

class AulaAIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register LLM services
        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });

        $this->app->singleton(AnthropicService::class, function ($app) {
            return new AnthropicService();
        });

        $this->app->singleton(LLMFactory::class, function ($app) {
            return new LLMFactory();
        });

        // Register Aula client
        $this->app->singleton(AulaClient::class, function ($app) {
            return new AulaClient();
        });

        // Register Agent factory
        $this->app->singleton(AgentFactory::class, function ($app) {
            return new AgentFactory(
                $app->make(LLMFactory::class),
                $app->make(AulaClient::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
