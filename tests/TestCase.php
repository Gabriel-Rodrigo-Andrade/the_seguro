<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app->make('db')->connection();
        if ($app->configurationIsCached() || ! $app->environment('testing')
            || $connection->getDriverName() !== 'pgsql'
            || $connection->getConfig('host') !== '127.0.0.1'
            || $connection->getConfig('database') !== 'theseguro_dev') {
            throw new \RuntimeException('Testes permitidos somente no PostgreSQL local theseguro_dev, sem cache de configuração.');
        }
        Http::preventStrayRequests();

        return $app;
    }
}
