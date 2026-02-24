# OG Pilot PHP

> [!IMPORTANT]  
> An active [OG Pilot](https://ogpilot.com?ref=og-pilot-php) subscription is required to use this package.

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
    // 'strip_extensions' => true,
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

### HTTP behavior

- Requests are sent as `POST` to `https://ogpilot.com/api/v1/images` (or your configured `base_url`).
- Redirect responses are followed automatically.
- The signed JWT is still passed as the `token` query parameter.
- In URL mode (`json: false`), successful requests return the final redirected URL when available, otherwise the `Location` header, then the original signed request URL as a fallback.
- When `json` is `true`, the client sends `Accept: application/json`.
- If image creation fails (request/configuration/validation/etc.), `createImage` does not throw. It logs at error level and returns:
  - `null` in URL mode
  - `['image_url' => null]` in JSON mode

### Template helpers

`createImage` defaults to the `page` template when `template` is omitted.
Supported templates: `page`, `blog_post`, `podcast`, `product`, `event`, `book`, `company`, `portfolio`, `github`.

Use these helpers to force a specific template:

- `createBlogPostImage(...)`
- `createPodcastImage(...)`
- `createProductImage(...)`
- `createEventImage(...)`
- `createBookImage(...)`
- `createCompanyImage(...)`
- `createPortfolioImage(...)`
- For `github`, use `createImage(['template' => 'github', ...])` (no dedicated helper yet).

These helpers are available on both `Sunergos\OgPilot\OgPilot` and `Sunergos\OgPilot\Client`.

```php
$imageUrl = OgPilot::createBlogPostImage([
    'title' => 'How to Build Amazing OG Images',
    'author_name' => 'Jane Smith',
    'publish_date' => '2024-01-15',
]);
```

<!-- OG_SAMPLES_START -->
## OG Image Examples

All sample payloads set explicit `bg_color`, `text_color`, and logo/avatar URLs to avoid default branding fallbacks.

For templates that support custom images, this set includes both `with_custom_image` and `without_custom_image` variants. (`github` currently has no custom image slot.)

Avatar-style fields (for example `author_avatar_url`) use DiceBear Avataaars, e.g. `https://api.dicebear.com/7.x/avataaars/svg?seed=JaneSmith`.

### Sample Gallery

| Template | Variant | Preview |
|---|---|---|
| `page` | `with custom image` | ![page_with_custom_image](https://img.ogpilot.com/1f6oY498I6SiNfqGDjwdHLNpwmeU264t2OL0k7tY8Mw/plain/s3://og-pilot-development/eoo5v45d766hf22j4r2al60ktali) |
| `page` | `without custom image` | ![page_without_custom_image](https://img.ogpilot.com/9MZdTcTRyOoRqpTLll__EvDimmgojZESfZWokDqXeZM/plain/s3://og-pilot-development/wfa9es2wuvp6btjiriekk53swp6n) |
| `blog_post` | `with custom image` | ![blog_post_with_custom_image](https://img.ogpilot.com/RBBQZnBrAKcVmFjJg6UtNqX8P6nRRQdGLrlJNWYif7I/plain/s3://og-pilot-development/je7pj816exul9umhyszqpnbxelmd) |
| `blog_post` | `without custom image` | ![blog_post_without_custom_image](https://img.ogpilot.com/yP1B7OrLOy9Iu9JDSNk9Veys3ESCuCSBM9il2wq13V4/plain/s3://og-pilot-development/6aei8frvun6kvqojoor1hqack31y) |
| `podcast` | `with custom image` | ![podcast_with_custom_image](https://img.ogpilot.com/rzOOt7PWJ44OEwpKnntMLZaPvtl76DA3yRlj6B6N-Cc/plain/s3://og-pilot-development/fmkeblwertneuy4p82foeg5rfltl) |
| `podcast` | `without custom image` | ![podcast_without_custom_image](https://img.ogpilot.com/5UWWFG2J5bNRDOhDkN96ZG_g0XI9ULGDFgQkuVTjCYQ/plain/s3://og-pilot-development/yyhmo7lamj1n99i2n6dyttwungmq) |
| `product` | `with custom image` | ![product_with_custom_image](https://img.ogpilot.com/mzmHDMjyAX4VlpJanMT3zpmIJgSuClYI5eofhFpSJNQ/plain/s3://og-pilot-development/8ls2legb316l9vak40nu388uzy2t) |
| `product` | `without custom image` | ![product_without_custom_image](https://img.ogpilot.com/kK6d7xU3EWT5WKC6jKCw1rhJDmv9bwvRn2S-nShV4NA/plain/s3://og-pilot-development/52ns2l1ll7hjhfg0p3wn5c9pqtr5) |
| `event` | `with custom image` | ![event_with_custom_image](https://img.ogpilot.com/A7nxHkYs4xN4c1PhH2KQSWoB4ALwBdpP0HPiAss9_70/plain/s3://og-pilot-development/vjkdf6cx82dvdxmhiwtvrvkl976d) |
| `event` | `without custom image` | ![event_without_custom_image](https://img.ogpilot.com/elpfu28vJ57XCGx3npKhCwXqqJnPoqwCm8Aj5SLkWsM/plain/s3://og-pilot-development/vpte818nvtegc60ta98q7pmw91c8) |
| `book` | `with custom image` | ![book_with_custom_image](https://img.ogpilot.com/7pidSkvU_l0RzY9xBOLSa2x-jDWWvx14Gtv-KMDCGLw/plain/s3://og-pilot-development/cwnhb631ls2olk0yzkukmr5dnf7e) |
| `book` | `without custom image` | ![book_without_custom_image](https://img.ogpilot.com/FpMbBN15SLgaK9FEX07xXcT5dQIWyZkFdHaZ9i5wx3U/plain/s3://og-pilot-development/0rzisfn2qswdzsz641rl3s29ngu9) |
| `portfolio` | `with custom image` | ![portfolio_with_custom_image](https://img.ogpilot.com/gauiw2MMcNLLSFnQ07Hq3flv4S0L-89lnnjUOO0VgYU/plain/s3://og-pilot-development/qok04llq0ff3d2lherudwlhqxslm) |
| `portfolio` | `without custom image` | ![portfolio_without_custom_image](https://img.ogpilot.com/jVck9SDPlei0gaHq44_itLoVzn2wINrCP3XCTQF3SYs/plain/s3://og-pilot-development/jxa1s7dtibaeqamh0fsymwz7uqrx) |
| `company` | `with custom image` | ![company_with_custom_image](https://img.ogpilot.com/rji7hNgoxRM1KF2GofIO9gqXJQNg5CqlPWbhbGpR4FA/plain/s3://og-pilot-development/2xl6zi3zgjm74izr46efduzhmbrr) |
| `company` | `without custom image` | ![company_without_custom_image](https://img.ogpilot.com/PgGl9a6xPmG0Tn7qKmZZUczrv43cNLxzyISsbHG8_oE/plain/s3://og-pilot-development/6gmm6jg5r8ya27r3tr215edfd972) |
| `github` | `default` | ![github_activity](https://img.ogpilot.com/0dxm83dTyNCg5Dq_GZFT4SqsRyTWUO31d0HQwjIq0-A/plain/s3://og-pilot-development/jlhwskjsx08x56attaljw3ce0p65) |

### Parameters Used

<details>
<summary>Exact payloads for these samples</summary>

```json
[
  {
    "id": "page_with_custom_image",
    "template": "page",
    "variant": "with_custom_image",
    "params": {
      "title": "Acme Robotics Product Updates",
      "description": "See what shipped this week across the web app.",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.notion.so",
      "image_url": "https://images.unsplash.com/photo-1558655146-d09347e92766?w=1400&q=80",
      "bg_color": "#0B1220",
      "text_color": "#F8FAFC",
      "template": "page"
    },
    "image_url": "https://img.ogpilot.com/1f6oY498I6SiNfqGDjwdHLNpwmeU264t2OL0k7tY8Mw/plain/s3://og-pilot-development/eoo5v45d766hf22j4r2al60ktali"
  },
  {
    "id": "page_without_custom_image",
    "template": "page",
    "variant": "without_custom_image",
    "params": {
      "title": "Notion AI Workspace for Product Teams",
      "description": "Docs, specs, and roadmaps in one living workspace.",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.notion.so",
      "bg_color": "#111827",
      "text_color": "#E5E7EB",
      "template": "page"
    },
    "image_url": "https://img.ogpilot.com/9MZdTcTRyOoRqpTLll__EvDimmgojZESfZWokDqXeZM/plain/s3://og-pilot-development/wfa9es2wuvp6btjiriekk53swp6n"
  },
  {
    "id": "blog_post_with_custom_image",
    "template": "blog_post",
    "variant": "with_custom_image",
    "params": {
      "title": "How Stripe Reduced Checkout Abandonment by 18%",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fstripe.com",
      "image_url": "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1400&q=80",
      "author_name": "Maya Patel",
      "author_avatar_url": "https://api.dicebear.com/7.x/avataaars/svg?seed=MayaPatel",
      "publish_date": "2026-02-24",
      "bg_color": "#0F172A",
      "text_color": "#F8FAFC",
      "template": "blog_post"
    },
    "image_url": "https://img.ogpilot.com/RBBQZnBrAKcVmFjJg6UtNqX8P6nRRQdGLrlJNWYif7I/plain/s3://og-pilot-development/je7pj816exul9umhyszqpnbxelmd"
  },
  {
    "id": "blog_post_without_custom_image",
    "template": "blog_post",
    "variant": "without_custom_image",
    "params": {
      "title": "Inside Vercel's Edge Rendering Playbook",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fvercel.com",
      "author_name": "Daniel Kim",
      "author_avatar_url": "https://api.dicebear.com/7.x/avataaars/svg?seed=DanielKim",
      "publish_date": "2026-02-18",
      "bg_color": "#111827",
      "text_color": "#E5E7EB",
      "template": "blog_post"
    },
    "image_url": "https://img.ogpilot.com/yP1B7OrLOy9Iu9JDSNk9Veys3ESCuCSBM9il2wq13V4/plain/s3://og-pilot-development/6aei8frvun6kvqojoor1hqack31y"
  },
  {
    "id": "podcast_with_custom_image",
    "template": "podcast",
    "variant": "with_custom_image",
    "params": {
      "title": "Indie Hackers Podcast: Pricing Experiments That Worked",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.spotify.com",
      "image_url": "https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=1400&q=80",
      "episode_date": "2026-02-21",
      "bg_color": "#18181B",
      "text_color": "#FAFAFA",
      "template": "podcast"
    },
    "image_url": "https://img.ogpilot.com/rzOOt7PWJ44OEwpKnntMLZaPvtl76DA3yRlj6B6N-Cc/plain/s3://og-pilot-development/fmkeblwertneuy4p82foeg5rfltl"
  },
  {
    "id": "podcast_without_custom_image",
    "template": "podcast",
    "variant": "without_custom_image",
    "params": {
      "title": "Shopify Masters: Building a 7-Figure DTC Brand",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.shopify.com",
      "episode_date": "2026-02-19",
      "bg_color": "#0B1120",
      "text_color": "#E2E8F0",
      "template": "podcast"
    },
    "image_url": "https://img.ogpilot.com/5UWWFG2J5bNRDOhDkN96ZG_g0XI9ULGDFgQkuVTjCYQ/plain/s3://og-pilot-development/yyhmo7lamj1n99i2n6dyttwungmq"
  },
  {
    "id": "product_with_custom_image",
    "template": "product",
    "variant": "with_custom_image",
    "params": {
      "title": "Allbirds Tree Dasher 3",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.allbirds.com",
      "image_url": "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=1400&q=80",
      "unique_selling_point": "Free shipping + 30-day returns",
      "bg_color": "#F8FAFC",
      "text_color": "#0F172A",
      "template": "product"
    },
    "image_url": "https://img.ogpilot.com/mzmHDMjyAX4VlpJanMT3zpmIJgSuClYI5eofhFpSJNQ/plain/s3://og-pilot-development/8ls2legb316l9vak40nu388uzy2t"
  },
  {
    "id": "product_without_custom_image",
    "template": "product",
    "variant": "without_custom_image",
    "params": {
      "title": "Bose QuietComfort Ultra",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.bose.com",
      "unique_selling_point": "Save $70 this weekend",
      "bg_color": "#111827",
      "text_color": "#F9FAFB",
      "template": "product"
    },
    "image_url": "https://img.ogpilot.com/kK6d7xU3EWT5WKC6jKCw1rhJDmv9bwvRn2S-nShV4NA/plain/s3://og-pilot-development/52ns2l1ll7hjhfg0p3wn5c9pqtr5"
  },
  {
    "id": "event_with_custom_image",
    "template": "event",
    "variant": "with_custom_image",
    "params": {
      "title": "Launch Week Berlin 2026",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.eventbrite.com",
      "image_url": "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1400&q=80",
      "event_date": "2026-06-12/2026-06-14",
      "event_location": "Station Berlin",
      "bg_color": "#1E1B4B",
      "text_color": "#F5F3FF",
      "template": "event"
    },
    "image_url": "https://img.ogpilot.com/A7nxHkYs4xN4c1PhH2KQSWoB4ALwBdpP0HPiAss9_70/plain/s3://og-pilot-development/vjkdf6cx82dvdxmhiwtvrvkl976d"
  },
  {
    "id": "event_without_custom_image",
    "template": "event",
    "variant": "without_custom_image",
    "params": {
      "title": "RubyConf Chicago 2026",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Frubyconf.org",
      "event_date": "2026-11-17",
      "event_location": "Chicago, IL",
      "bg_color": "#312E81",
      "text_color": "#EEF2FF",
      "template": "event"
    },
    "image_url": "https://img.ogpilot.com/elpfu28vJ57XCGx3npKhCwXqqJnPoqwCm8Aj5SLkWsM/plain/s3://og-pilot-development/vpte818nvtegc60ta98q7pmw91c8"
  },
  {
    "id": "book_with_custom_image",
    "template": "book",
    "variant": "with_custom_image",
    "params": {
      "title": "Designing APIs for Humans",
      "description": "A practical handbook for product-minded engineers.",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.oreilly.com",
      "image_url": "https://images.unsplash.com/photo-1512820790803-83ca734da794?w=1400&q=80",
      "book_author": "Alex Turner",
      "book_series_number": "2",
      "book_genre": "Technology",
      "bg_color": "#172554",
      "text_color": "#EFF6FF",
      "template": "book"
    },
    "image_url": "https://img.ogpilot.com/7pidSkvU_l0RzY9xBOLSa2x-jDWWvx14Gtv-KMDCGLw/plain/s3://og-pilot-development/cwnhb631ls2olk0yzkukmr5dnf7e"
  },
  {
    "id": "book_without_custom_image",
    "template": "book",
    "variant": "without_custom_image",
    "params": {
      "title": "The Product Operating System",
      "description": "How modern teams ship with confidence.",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.penguinrandomhouse.com",
      "book_author": "Sofia Ramirez",
      "book_series_number": "1",
      "book_genre": "Business",
      "bg_color": "#1E293B",
      "text_color": "#F8FAFC",
      "template": "book"
    },
    "image_url": "https://img.ogpilot.com/FpMbBN15SLgaK9FEX07xXcT5dQIWyZkFdHaZ9i5wx3U/plain/s3://og-pilot-development/0rzisfn2qswdzsz641rl3s29ngu9"
  },
  {
    "id": "portfolio_with_custom_image",
    "template": "portfolio",
    "variant": "with_custom_image",
    "params": {
      "title": "Maya Chen • Product Designer",
      "description": "Fintech UX, design systems, and prototyping.",
      "logo_url": "https://api.dicebear.com/7.x/avataaars/svg?seed=MayaChen&size=64",
      "image_url": "https://images.unsplash.com/photo-1557672172-298e090bd0f1?w=1400&q=80",
      "bg_color": "#1F2937",
      "text_color": "#F9FAFB",
      "template": "portfolio"
    },
    "image_url": "https://img.ogpilot.com/gauiw2MMcNLLSFnQ07Hq3flv4S0L-89lnnjUOO0VgYU/plain/s3://og-pilot-development/qok04llq0ff3d2lherudwlhqxslm"
  },
  {
    "id": "portfolio_without_custom_image",
    "template": "portfolio",
    "variant": "without_custom_image",
    "params": {
      "title": "Arjun Rao • Frontend Engineer",
      "description": "React performance, accessibility, and DX.",
      "logo_url": "https://api.dicebear.com/7.x/avataaars/svg?seed=ArjunRao&size=64",
      "bg_color": "#0F172A",
      "text_color": "#E2E8F0",
      "template": "portfolio"
    },
    "image_url": "https://img.ogpilot.com/jVck9SDPlei0gaHq44_itLoVzn2wINrCP3XCTQF3SYs/plain/s3://og-pilot-development/jxa1s7dtibaeqamh0fsymwz7uqrx"
  },
  {
    "id": "company_with_custom_image",
    "template": "company",
    "variant": "with_custom_image",
    "params": {
      "title": "Notion",
      "description": "4 job openings",
      "logo_url": "https://www.google.com/s2/favicons?sz=128&domain_url=https%3A%2F%2Fwww.notion.so",
      "bg_color": "#111827",
      "text_color": "#F9FAFB",
      "template": "company"
    },
    "image_url": "https://img.ogpilot.com/rji7hNgoxRM1KF2GofIO9gqXJQNg5CqlPWbhbGpR4FA/plain/s3://og-pilot-development/2xl6zi3zgjm74izr46efduzhmbrr"
  },
  {
    "id": "company_without_custom_image",
    "template": "company",
    "variant": "without_custom_image",
    "params": {
      "title": "Linear",
      "description": "12 job openings",
      "company_logo_url": "https://www.google.com/s2/favicons?sz=256&domain_url=https%3A%2F%2Flinear.app",
      "bg_color": "#0B1220",
      "text_color": "#E5E7EB",
      "template": "company",
      "iss": "gitdigest.ai"
    },
    "image_url": "https://img.ogpilot.com/PgGl9a6xPmG0Tn7qKmZZUczrv43cNLxzyISsbHG8_oE/plain/s3://og-pilot-development/6gmm6jg5r8ya27r3tr215edfd972"
  },
  {
    "id": "github_activity",
    "template": "github",
    "variant": "default",
    "params": {
      "title": "rails/rails",
      "description": "Recent merged PRs, reviews, and commit activity.",
      "accent_color": "#22C55E",
      "bg_color": "#0B1220",
      "text_color": "#E5E7EB",
      "contributions": [
        {
          "date": "2025-10-28",
          "count": 6
        },
        {
          "date": "2025-11-01",
          "count": 3
        },
        {
          "date": "2025-11-05",
          "count": 9
        },
        {
          "date": "2025-11-08",
          "count": 12
        },
        {
          "date": "2025-11-12",
          "count": 7
        },
        {
          "date": "2025-11-16",
          "count": 11
        },
        {
          "date": "2025-11-20",
          "count": 5
        },
        {
          "date": "2025-11-24",
          "count": 14
        },
        {
          "date": "2025-11-28",
          "count": 8
        },
        {
          "date": "2025-12-02",
          "count": 4
        },
        {
          "date": "2025-12-06",
          "count": 10
        },
        {
          "date": "2025-12-10",
          "count": 15
        },
        {
          "date": "2025-12-14",
          "count": 6
        },
        {
          "date": "2025-12-18",
          "count": 9
        },
        {
          "date": "2025-12-22",
          "count": 13
        },
        {
          "date": "2025-12-26",
          "count": 4
        },
        {
          "date": "2025-12-30",
          "count": 7
        },
        {
          "date": "2026-01-03",
          "count": 11
        },
        {
          "date": "2026-01-07",
          "count": 16
        },
        {
          "date": "2026-01-11",
          "count": 12
        },
        {
          "date": "2026-01-15",
          "count": 6
        },
        {
          "date": "2026-01-19",
          "count": 8
        },
        {
          "date": "2026-01-23",
          "count": 14
        },
        {
          "date": "2026-01-27",
          "count": 9
        },
        {
          "date": "2026-01-31",
          "count": 5
        },
        {
          "date": "2026-02-04",
          "count": 13
        },
        {
          "date": "2026-02-08",
          "count": 17
        },
        {
          "date": "2026-02-12",
          "count": 10
        },
        {
          "date": "2026-02-16",
          "count": 7
        },
        {
          "date": "2026-02-20",
          "count": 12
        },
        {
          "date": "2026-02-24",
          "count": 9
        }
      ],
      "template": "github"
    },
    "image_url": "https://img.ogpilot.com/0dxm83dTyNCg5Dq_GZFT4SqsRyTWUO31d0HQwjIq0-A/plain/s3://og-pilot-development/jlhwskjsx08x56attaljw3ce0p65"
  }
]
```

</details>

<!-- OG_SAMPLES_END -->





## Path Handling

The `path` parameter enhances OG Pilot analytics by tracking which OG images perform better across different pages on your site. By capturing the request path, you get granular insights into click-through rates and engagement for each OG image.

The client automatically injects a `path` parameter on every request:

| Option | Behavior |
|--------|----------|
| `default: false` | Uses the current request path when available (via framework auto-detection, request context, or `$_SERVER`), then falls back to `/` |
| `default: true` | Forces the `path` parameter to `/`, regardless of the current request (unless `path` is provided explicitly) |
| `path: "/..."` | Uses the provided path verbatim (normalized to start with `/`), overriding auto-resolution |

### Automatic Framework Detection

The library automatically detects the current request path from popular PHP frameworks:

**Laravel** - Zero setup required! The library automatically uses Laravel's `request()` helper to get the current path. Just use OG Pilot anywhere in your Laravel application:

```php
use Sunergos\OgPilot\Facades\OgPilot;

// In a controller, view, or anywhere in your Laravel app
$imageUrl = OgPilot::createImage([
    'template' => 'blog_post',
    'title' => 'My Blog Post',
]);
// path is automatically captured from the current request
```

### Manual Request Context (Other Frameworks)

For non-Laravel frameworks, set the current request context manually:

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

**Symfony Middleware:**

```php
use Sunergos\OgPilot\OgPilot;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class OgPilotListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        OgPilot::setCurrentRequest([
            'path' => $request->getRequestUri()
        ]);
    }
}
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
| `strip_extensions` | `OG_PILOT_STRIP_EXTENSIONS` | `true` | Strip file extensions from resolved paths (see [Strip extensions](#strip-extensions)) |

### Strip extensions

When `strip_extensions` is enabled, the client removes file extensions from the
last segment of every resolved path. This ensures that `/docs`, `/docs.md`,
`/docs.php`, and `/docs.html` all resolve to `"/docs"`, so analytics are
consolidated under a single path regardless of the URL extension.

Multiple extensions are also stripped (`/archive.tar.gz` becomes `/archive`).
Dotfiles like `/.hidden` are left unchanged. Query strings are preserved.

```php
// Laravel (.env)
OG_PILOT_STRIP_EXTENSIONS=true

// Standalone PHP
OgPilot::setConfig([
    'api_key' => 'your-api-key',
    'domain' => 'your-domain.com',
    'strip_extensions' => true,
]);

// All of these resolve to path "/docs":
OgPilot::createImage(['title' => 'Docs', 'path' => '/docs']);
OgPilot::createImage(['title' => 'Docs', 'path' => '/docs.md']);
OgPilot::createImage(['title' => 'Docs', 'path' => '/docs.php']);

// Nested paths work too: /blog/my-post.html → /blog/my-post
// Query strings are preserved: /docs.md?ref=main → /docs?ref=main
// Dotfiles are unchanged: /.hidden stays /.hidden
```

## Error Handling

`createImage` is fail-safe and does not throw to your application on runtime failures.
If generation fails for any reason (request/configuration/validation/etc.), the client logs
an error and returns a fallback value.

```php
$imageUrl = OgPilot::createImage(['title' => 'My Image']);
if ($imageUrl === null) {
    // Failed to generate image in URL mode
}

$json = OgPilot::createImage(['title' => 'My Image'], ['json' => true]);
if (($json['image_url'] ?? null) === null) {
    // Failed to generate image in JSON mode
}
```

In Laravel, errors are logged through `Log::error(...)`. In non-Laravel environments,
the client falls back to PHP `error_log(...)`.

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
