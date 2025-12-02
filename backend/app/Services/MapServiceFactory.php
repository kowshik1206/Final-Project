<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

class MapServiceFactory
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Return an instance of MapServiceInterface based on config, with safe fallback to fake.
     */
    public function make(): MapServiceInterface
    {
        $provider = config('services.maps.provider', env('MAPS_PROVIDER', 'fake'));

        try {
            if ($provider === 'google') {
                $svc = app(MapServiceGoogle::class);
                // quick smoke call to ensure connectivity? skip — let controller handle exceptions
                return $svc;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('MapServiceFactory: google init failed, falling back to fake', ['err' => $e->getMessage()]);
        }

        return app(MapServiceFake::class);
    }
}
