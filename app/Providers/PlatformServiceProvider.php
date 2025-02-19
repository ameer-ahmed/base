<?php

namespace App\Providers;

use App\Enums\Platform;
use App\Http\Services\Api\V1\Auth\AuthMobileService;
use App\Http\Services\Api\V1\Auth\AuthService;
use App\Http\Services\Api\V1\Auth\AuthWebService;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    private const VERSIONS = [1];
    private const FALLBACK_VERSION = 1;
    private const FALLBACK_PLATFORM = Platform::WEBSITE;

    private ?int $version;
    private ?Platform $platform;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->detectPlatformAndVersion();
    }

    private function detectPlatformAndVersion(): void
    {
        foreach (self::VERSIONS as $version) {
            foreach (Platform::cases() as $platformCase) {
                $pattern = "api/v$version/{$platformCase->value}/*";

                if (request()->is($pattern)) {
                    $this->version = $version;
                    $this->platform = $platformCase;
                    return;
                }
            }
        }

        $this->version = self::FALLBACK_VERSION;
        $this->platform = self::FALLBACK_PLATFORM;
    }

    public function register(): void
    {
        $this->bindServices();
    }

    private function bindServices(): void
    {
        $this->app->bind(
            AuthService::class,
            $this->resolve(AuthService::class)
        );
    }

    private function resolve(string $abstract): string
    {
        $implementations = $this->getConcreteImplementations($abstract);

        foreach ($implementations as $implementation) {
            if ($implementation::platform() === $this->platform) {
                return $implementation;
            }
        }

        throw new \RuntimeException("No implementation found for {$abstract}");
    }

    private function getConcreteImplementations(string $abstract): array
    {
        return match ($abstract) {
            AuthService::class => [
                AuthWebService::class,
                AuthMobileService::class,
            ],
            default => [],
        };
    }
}
