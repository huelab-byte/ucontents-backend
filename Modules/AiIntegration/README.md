# AI Integration Module

This module provides centralized AI integration functionality for the application, supporting multiple AI providers and managing API keys, usage tracking, and prompt templates.

## Features

### 1. AI Provider Management
- Support for multiple AI providers:
  - OpenAI
  - Azure OpenAI
  - Anthropic (Claude)
  - Google (Gemini)
  - DeepSeek
  - xAI (Grok)
- Provider configuration and initialization
- Model support tracking

### 2. API Key Management
- Multiple API keys per provider
- Enable/disable API keys
- Priority-based key selection
- Rate limiting per API key
- Encrypted storage of API keys
- Usage tracking per key

### 3. AI Model Calling
- Centralized AI model calling service
- Automatic API key selection (best available or random)
- Support for custom API key selection
- Model settings (temperature, max_tokens, etc.)
- Usage logging and cost tracking

### 4. Usage Tracking & Analytics
- Detailed usage logs for all AI calls
- Token usage tracking
- Cost calculation
- Response time monitoring
- Statistics by provider, model, and user

### 5. Prompt Templates
- Reusable prompt templates
- Variable substitution
- Category organization
- Provider/model preferences
- System and user templates

## Database Structure

### Tables

1. **ai_providers** - AI service providers
2. **ai_api_keys** - API keys for providers
3. **ai_usage_logs** - Usage tracking
4. **ai_prompt_templates** - Prompt templates

## API Endpoints

### Admin Endpoints

#### AI Providers
- `GET /api/v1/admin/ai-providers` - List all providers
- `GET /api/v1/admin/ai-providers/{id}` - Get provider details
- `POST /api/v1/admin/ai-providers/initialize` - Initialize providers from config

#### API Keys
- `GET /api/v1/admin/ai-api-keys` - List all API keys
- `POST /api/v1/admin/ai-api-keys` - Create new API key
- `GET /api/v1/admin/ai-api-keys/{id}` - Get API key details
- `PUT/PATCH /api/v1/admin/ai-api-keys/{id}` - Update API key
- `DELETE /api/v1/admin/ai-api-keys/{id}` - Delete API key
- `POST /api/v1/admin/ai-api-keys/{id}/enable` - Enable API key
- `POST /api/v1/admin/ai-api-keys/{id}/disable` - Disable API key

#### Usage & Statistics
- `GET /api/v1/admin/ai-usage` - List usage logs
- `GET /api/v1/admin/ai-usage/statistics` - Get usage statistics

#### Prompt Templates
- `GET /api/v1/admin/ai-prompt-templates` - List templates
- `POST /api/v1/admin/ai-prompt-templates` - Create template
- `GET /api/v1/admin/ai-prompt-templates/{id}` - Get template
- `PUT/PATCH /api/v1/admin/ai-prompt-templates/{id}` - Update template
- `DELETE /api/v1/admin/ai-prompt-templates/{id}` - Delete template

### Customer Endpoints

#### AI Model Calls
- `POST /api/v1/customer/ai/call` - Call an AI model

#### Prompt Templates
- `GET /api/v1/customer/ai-prompt-templates` - List active templates
- `GET /api/v1/customer/ai-prompt-templates/{id}` - Get template
- `POST /api/v1/customer/ai-prompt-templates/{id}/render` - Render template with variables

## Usage Examples

### Calling an AI Model

```php
use Modules\AiIntegration\Services\AiModelCallService;
use Modules\AiIntegration\DTOs\AiModelCallDTO;

$service = app(AiModelCallService::class);

$dto = new AiModelCallDTO(
    providerSlug: 'openai',
    model: 'gpt-4',
    prompt: 'Write a blog post about AI',
    settings: [
        'temperature' => 0.7,
        'max_tokens' => 1000,
    ],
    module: 'ContentGeneration',
    feature: 'blog_post_generation'
);

$response = $service->callModel($dto, auth()->id());
```

### Using Prompt Templates

```php
use Modules\AiIntegration\Models\AiPromptTemplate;

$template = AiPromptTemplate::where('slug', 'blog-post')->first();
$rendered = $template->render([
    'topic' => 'AI',
    'tone' => 'professional',
]);
```

### Getting Best API Key

```php
use Modules\AiIntegration\Services\AiApiKeyService;

$service = app(AiApiKeyService::class);
$apiKey = $service->getBestApiKey('openai');
```

## Permissions

- `manage_ai_providers` - Manage AI providers
- `manage_ai_api_keys` - Manage API keys
- `view_ai_usage` - View usage statistics
- `manage_prompt_templates` - Manage prompt templates
- `call_ai_models` - Call AI models (customer)
- `use_prompt_templates` - Use prompt templates (customer)

## Configuration

Module configuration is in `config/module.php`:

```php
'providers' => [
    'openai' => [
        'name' => 'OpenAI',
        'models' => ['gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo'],
        'base_url' => 'https://api.openai.com/v1',
    ],
    // ... other providers
],
```

## Initialization

After installing the module, initialize providers:

```bash
php artisan tinker
```

```php
app(\Modules\AiIntegration\Services\AiProviderService::class)->initializeProviders();
```

Or via API:

```bash
POST /api/v1/admin/ai-providers/initialize
```

## Security

- API keys are encrypted using Laravel's Crypt facade
- Rate limiting per API key
- Usage logging for audit trails
- Permission-based access control

## Future Enhancements

- [ ] Streaming responses support
- [ ] Function calling support
- [ ] Fine-tuning management
- [ ] Cost alerts and budgets
- [ ] API key rotation automation
- [ ] Multi-tenant API key isolation
