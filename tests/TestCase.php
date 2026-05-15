<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->isolateTestingCacheFiles();

        return parent::createApplication();
    }

    private function isolateTestingCacheFiles(): void
    {
        foreach ([
            'APP_CONFIG_CACHE' => 'bootstrap/cache/testing-config.php',
            'APP_EVENTS_CACHE' => 'bootstrap/cache/testing-events.php',
            'APP_ROUTES_CACHE' => 'bootstrap/cache/testing-routes.php',
        ] as $key => $path) {
            putenv($key.'='.$path);
            $_ENV[$key] = $path;
            $_SERVER[$key] = $path;
        }
    }
}
