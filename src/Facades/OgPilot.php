<?php

declare(strict_types=1);

namespace Sunergos\OgPilot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Sunergos\OgPilot\Configuration config()
 * @method static \Sunergos\OgPilot\Configuration configure(callable $callback)
 * @method static \Sunergos\OgPilot\Configuration setConfig(array $options)
 * @method static void resetConfig()
 * @method static \Sunergos\OgPilot\Client client()
 * @method static \Sunergos\OgPilot\Client createClient(array $options = [])
 * @method static string|array createImage(array $params = [], array $options = [])
 * @method static string|array createBlogPostImage(array $params = [], array $options = [])
 * @method static string|array createPodcastImage(array $params = [], array $options = [])
 * @method static string|array createProductImage(array $params = [], array $options = [])
 * @method static string|array createEventImage(array $params = [], array $options = [])
 * @method static string|array createBookImage(array $params = [], array $options = [])
 * @method static string|array createCompanyImage(array $params = [], array $options = [])
 * @method static string|array createPortfolioImage(array $params = [], array $options = [])
 *
 * @see \Sunergos\OgPilot\OgPilot
 */
class OgPilot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Sunergos\OgPilot\OgPilot::class;
    }
}
