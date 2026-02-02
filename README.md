# OG Pilot PHP

A PHP client for generating OG Pilot Open Graph images via signed JWTs, with first-class Laravel support.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

Install the package via Composer:

```bash
composer require sunergos/og-pilot-php
```

### Laravel

The package supports Laravel's auto-discovery, so the service provider and facade will be registered automatically.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=og-pilot-config
```

Add your credentials to your `.env` file:

```env
OG_PILOT_API_KEY=your-api-key
OG_PILOT_DOMAIN=your-domain.com
```

### Standalone PHP

For non-Laravel projects, configure the client directly:

```php
use Sunergos\OgPilot\OgPilot;

OgPilot::setConfig([
    'api_key' => 'your-api-key',
    'domain' => 'your-domain.com',
]);
```

Or use environment variables `OG_PILOT_API_KEY` and `OG_PILOT_DOMAIN`.

## Usage

### Laravel (using Facade)

```php
use Sunergos\OgPilot\Facades\OgPilot;

// Generate an image URL
$imageUrl = OgPilot::createImage([
    'template' => 'blog_post',
    'title' => 'How to Build Amazing OG Images',
    'description' => 'A complete guide to social media previews',
    'bg_color' => '#1a1a1a',
    'text_color' => '#ffffff',
    'author_name' => 'Jane Smith',
    'publish_date' => '2024-01-15',
]);

// With cache refresh (using iat)
$imageUrl = OgPilot::createImage([
    'template' => 'blog_post',
    'title' => 'My Blog Post',
], [
    'iat' => time(), // Refresh cache daily
]);

// Get JSON metadata instead
$data = OgPilot::createImage([
    'template' => 'page',
    'title' => 'Hello OG Pilot',
], [
    'json' => true,
]);
```

### Standalone PHP

```php
use Sunergos\OgPilot\OgPilot;

// Configure once at application bootstrap
OgPilot::setConfig([
    'api_key' => 'your-api-key',
    'domain' => 'your-domain.com',
]);

// Generate an image URL
$imageUrl = OgPilot::createImage([
    'template' => 'blog_post',
    'title' => 'How to Build Amazing OG Images',
]);

// Or use the callback-style configuration
OgPilot::configure(function ($config) {
    $config->apiKey = 'your-api-key';
    $config->domain = 'your-domain.com';
});
```

### Using a Custom Client

Create a dedicated client with custom configuration:

```php
use Sunergos\OgPilot\OgPilot;
use Sunergos\OgPilot\Client;

// Using the factory method
$client = OgPilot::createClient([
    'api_key' => 'your-api-key',
    'domain' => 'your-domain.com',
    'connect_timeout' => 3.0,
    'timeout' => 8.0,
]);

$url = $client->createImage(['title' => 'Hello']);

// Or instantiate directly
$client = new Client([
    'api_key' => 'your-api-key',
    'domain' => 'your-domain.com',
]);
```

### Options

The `createImage` method accepts two arguments:

1. **params** (array): Image parameters sent to OG Pilot
   - `template`: Template name
   - `title`: Image title (required)
   - `description`: Image description
   - `path`: Request path for analytics (auto-resolved if not provided)
   - Any other template-specific parameters

2. **options** (array): Request options
   - `json`: Set to `true` to receive JSON metadata instead of a URL
   - `iat`: Timestamp for cache control (accepts Unix timestamp, DateTime, or milliseconds)
   - `headers`: Additional HTTP headers
   - `default`: Set to `true` to force path to "/" (useful for default OG images)

## Path Handling

The `path` parameter enhances OG Pilot analytics by tracking which OG images perform better across different pages on your site. By capturing the request path, you get granular insights into click-through rates and engagement for each OG image.

The client automatically injects a `path` parameter on every request:

| Option | Behavior |
|--------|----------|
| `default: false` | Uses the current request path when available (via request context or `$_SERVER`), then falls back to `/` |
| `default: true` | Forces the `path` parameter to `/`, regardless of the current request (unless `path` is provided explicitly) |
| `path: "/..."` | Uses the provided path verbatim (normalized to start with `/`), overriding auto-resolution |

### Setting Up Request Context

To enable automatic path resolution, set the current request context in your middleware:

**Laravel Middleware:**

```php
use Sunergos\OgPilot\OgPilot;

class OgPilotMiddleware
{
    public function handle($request, $next)
    {
        OgPilot::setCurrentRequest([
            'url' => $request->fullUrl(),
            'path' => $request->getRequestUri()
        ]);

        $response = $next($request);

        OgPilot::clearCurrentRequest();

        return $response;
    }
}
```

**Vanilla PHP:**

```php
use Sunergos\OgPilot\OgPilot;

// At the start of your request handling
OgPilot::setCurrentRequest([
    'url' => $_SERVER['REQUEST_URI'] ?? '/'
]);

// Your application code...
$imageUrl = OgPilot::createImage(['title' => 'My Page']);
// path is automatically set from REQUEST_URI

// At the end of your request
OgPilot::clearCurrentRequest();
```

**Using withRequestContext (recommended):**

```php
use Sunergos\OgPilot\OgPilot;

$imageUrl = OgPilot::withRequestContext(
    ['url' => $_SERVER['REQUEST_URI']],
    fn() => OgPilot::createImage(['title' => 'My Page'])
);
```

### Manual Path Override

```php
$imageUrl = OgPilot::createImage([
    'template' => 'page',
    'title' => 'Hello OG Pilot',
    'path' => '/pricing?plan=pro'
]);
```

### Default Path

```php
$imageUrl = OgPilot::createImage([
    'template' => 'blog_post',
    'title' => 'Default OG Image',
], [
    'default' => true
]);
// path is set to "/"
```

## Configuration Options

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| `api_key` | `OG_PILOT_API_KEY` | `null` | Your OG Pilot API key |
| `domain` | `OG_PILOT_DOMAIN` | `null` | Your domain |
| `base_url` | `OG_PILOT_BASE_URL` | `https://ogpilot.com` | API base URL |
| `connect_timeout` | `OG_PILOT_CONNECT_TIMEOUT` | `5.0` | Connection timeout in seconds |
| `timeout` | `OG_PILOT_TIMEOUT` | `10.0` | Request timeout in seconds |

## Error Handling

The package throws specific exceptions for different error scenarios:

```php
use Sunergos\OgPilot\Exceptions\ConfigurationException;
use Sunergos\OgPilot\Exceptions\RequestException;
use Sunergos\OgPilot\Exceptions\OgPilotException;

try {
    $imageUrl = OgPilot::createImage(['title' => 'My Image']);
} catch (ConfigurationException $e) {
    // Missing or invalid configuration (API key, domain)
} catch (RequestException $e) {
    // HTTP request failed
    $statusCode = $e->getStatusCode();
} catch (OgPilotException $e) {
    // Base exception for all OG Pilot errors
}
```

## Framework Notes

This library is intended for **server-side usage only**. Keep your API key private and do not expose it in client-side code.

- **Laravel**: Use in controllers, jobs, or any server-side code
- **Symfony**: Use in controllers or services
- **WordPress**: Use in theme functions or plugins (server-side only)

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
