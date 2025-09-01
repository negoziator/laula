# Aula AI - Laravel Implementation

A comprehensive AI-powered chat application built with Laravel, featuring intelligent agents for research and Danish school system (Aula) integration. This is a complete rewrite of the original Python-based Aula AI project, leveraging the full Laravel ecosystem.

## Features

- **Modern Laravel 12 Architecture** with Inertia.js and Vue 3
- **Multiple LLM Providers**: OpenAI (GPT-4o, GPT-4o Mini) and Anthropic (Claude 3.5 Sonnet, Claude 3 Haiku)
- **Intelligent Agents**:
  - **Research Agent**: Performs multi-step Google searches and web content fetching
  - **Aula Agent**: Integrates with the Danish Aula school system for profiles, messages, and calendar events
- **Real-time Chat Interface** with conversation history
- **Beautiful UI** built with Tailwind CSS and modern Vue.js components
- **Robust Error Handling** and logging
- **Environment-based Configuration** for all services
- **Database-driven Conversations** with message persistence

## Prerequisites

- PHP 8.4+
- Node.js 18+
- Composer
- MySQL/SQLite database
- API keys for the services you want to use:
  - OpenAI API key (optional)
  - Anthropic API key (optional)
  - Google Search API key and Custom Search Engine ID (optional)
  - Aula credentials for Danish school system integration (optional)

## Installation

1. **Clone and setup the project**:
   ```bash
   git clone <your-repository-url>
   cd laula
   composer install
   npm install
   ```

2. **Environment Configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure your environment variables** in `.env`:
   ```bash
   # Database (SQLite is default, but you can use MySQL)
   DB_CONNECTION=sqlite
   
   # OpenAI Configuration (optional)
   OPENAI_API_KEY=your_openai_api_key_here
   OPENAI_DEFAULT_MODEL=gpt-4o
   
   # Anthropic Configuration (optional)
   ANTHROPIC_API_KEY=your_anthropic_api_key_here
   ANTHROPIC_DEFAULT_MODEL=claude-3-5-sonnet-20241022
   
   # Google Search Configuration (optional, for Research Agent)
   GOOGLE_SEARCH_API_KEY=your_google_api_key_here
   GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id_here
   
   # Aula Configuration (optional, for Danish school system)
   AULA_USERNAME=your_aula_username
   AULA_PASSWORD=your_aula_password
   ```

4. **Database Setup**:
   ```bash
   php artisan migrate
   ```

5. **Build Frontend Assets**:
   ```bash
   npm run build
   # OR for development with hot reload:
   npm run dev
   ```

## Usage

1. **Start the Laravel server**:
   ```bash
   php artisan serve
   ```

2. **Access the application**:
   Open your browser and navigate to `http://localhost:8000`

3. **Start Chatting**:
   - Select an AI model (GPT-4o, Claude 3.5 Sonnet, etc.)
   - Choose an agent (Research Agent or Aula Agent)
   - Start typing your questions!

## Agent Capabilities

### Research Agent
- Performs intelligent Google searches based on your queries
- Fetches and analyzes web page content
- Combines multiple search results for comprehensive answers
- Perfect for research tasks, fact-checking, and information gathering

**Example queries**:
- "What are the latest developments in AI technology?"
- "Research the benefits of renewable energy"
- "Find information about Laravel 12 new features"

### Aula Agent
- Connects to the Danish Aula school communication system
- Fetches student profiles and institution information
- Retrieves messages from teachers and school administration
- Shows calendar events and school activities
- Provides daily presence/attendance overview

**Example queries**:
- "Show me my children's school information"
- "What messages do I have from the school?"
- "What's on the calendar for next week?"
- "Set active child to Emma and show today's schedule"

## Architecture

### Backend Structure
```
app/
├── Http/Controllers/Api/
│   └── ChatController.php          # API endpoints for chat functionality
├── Models/
│   ├── Conversation.php            # Conversation model with relationships
│   ├── Message.php                 # Message model with user/AI distinction
│   └── User.php                    # User model (for future authentication)
├── Services/
│   ├── LLM/
│   │   ├── LLMServiceInterface.php # Interface for LLM providers
│   │   ├── OpenAIService.php       # OpenAI integration
│   │   ├── AnthropicService.php    # Anthropic integration
│   │   └── LLMFactory.php          # Factory for creating LLM instances
│   ├── Agents/
│   │   ├── AgentInterface.php      # Interface for AI agents
│   │   ├── BaseAgent.php           # Base agent functionality
│   │   ├── ResearchAgent.php       # Google Search and web scraping
│   │   ├── AulaAgent.php           # Danish school system integration
│   │   └── AgentFactory.php        # Factory for creating agents
│   └── Aula/
│       └── AulaClient.php          # Aula API client implementation
└── Providers/
    └── AulaAIServiceProvider.php   # Service provider for dependency injection
```

### Frontend Structure
```
resources/js/Pages/Chat/
└── Index.vue                       # Main chat interface with Vue 3 Composition API
```

### Key Features
- **Service-oriented Architecture**: Clean separation of concerns with dedicated services
- **Factory Pattern**: Easy extension with new LLM providers and agents
- **Dependency Injection**: Proper Laravel service container usage
- **Modern Frontend**: Vue 3 with Composition API and Tailwind CSS
- **Real-time UI**: Responsive chat interface with typing indicators
- **Error Handling**: Comprehensive error handling and user feedback

## API Endpoints

- `POST /api/chat` - Send a message and get AI response
- `GET /api/models` - Get available LLM models
- `GET /api/agents` - Get available AI agents
- `GET /api/conversations` - Get user's conversation history
- `GET /api/conversations/{id}` - Get specific conversation with messages

## Configuration

### Adding New LLM Providers
1. Create a new service class implementing `LLMServiceInterface`
2. Register it in `LLMFactory`
3. Add configuration to `config/services.php`

### Adding New Agents
1. Create a new agent class extending `BaseAgent`
2. Implement the required methods (`getType`, `getSystemPrompt`, `getAvailableTools`)
3. Register it in `AgentFactory`

## Development

### Running in Development Mode
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Frontend development server (optional)
npm run dev
```

### Testing
```bash
# Run PHP tests
php artisan test

# Run frontend tests (if configured)
npm run test
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run static analysis with Larastan
./vendor/bin/phpstan analyse
```

## Troubleshooting

### Common Issues

1. **"No provider found for model" error**:
   - Check that your API keys are correctly set in `.env`
   - Ensure the model name matches exactly (case-sensitive)

2. **Aula integration not working**:
   - Verify your Aula credentials in `.env`
   - Note: Aula integration requires valid Danish school system credentials

3. **Google Search not working**:
   - Set up Google Custom Search API and get your API key
   - Create a Custom Search Engine and get the Engine ID
   - Add both to your `.env` file

4. **Frontend not loading**:
   - Run `npm run build` to compile assets
   - Check that Vite is properly configured

### Logs
Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Acknowledgments

- Original Python-based Aula AI project by A-Hoier
- Laravel framework and ecosystem
- Vue.js and Inertia.js for the modern frontend
- OpenAI and Anthropic for LLM capabilities
- Danish Aula school system integration

---

**Note**: This is a complete rewrite of the original Python project, built from the ground up with Laravel best practices and modern web technologies. All functionality from the original project has been preserved and enhanced with additional features and better architecture.
