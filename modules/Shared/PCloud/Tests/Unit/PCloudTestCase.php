<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Tests\Unit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

/**
 * Lightweight Laravel facade bootstrap without booting all app providers (no DB).
 */
abstract class PCloudTestCase extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(dirname(__DIR__, 5));
        $this->app->singleton('config', function () {
            return new ConfigRepository([
                'pcloud' => [
                    'enabled' => true,
                    'email' => 'test@example.com',
                    'password' => 'secret-pass',
                    'default_api_host' => 'https://api.pcloud.com',
                    'api_hosts' => [
                        1 => 'https://api.pcloud.com',
                        2 => 'https://eapi.pcloud.com',
                    ],
                    'root_folder' => 'Constrix Archive',
                    'dispatch' => 'sync',
                    'timeout' => 30,
                    'auth_cache_ttl' => 3500,
                ],
            ]);
        });
        $this->app->singleton('events', fn ($app) => new Dispatcher($app));
        $this->app->singleton(\Illuminate\Contracts\Events\Dispatcher::class, fn ($app) => $app['events']);

        Container::setInstance($this->app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        Http::swap(new HttpFactory($this->app['events']));
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    protected function configurePCloud(array $overrides = []): void
    {
        $current = $this->app['config']->get('pcloud', []);
        $this->app['config']->set('pcloud', array_replace($current, $overrides));
    }
}
