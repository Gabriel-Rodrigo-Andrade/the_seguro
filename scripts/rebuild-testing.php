<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

// Reconstrói exclusivamente o banco local de testes, nunca o banco da aplicação.
require __DIR__.'/../vendor/autoload.php';

foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '5432', 'DB_DATABASE' => 'theseguro_dev', 'DB_USERNAME' => 'theseguro', 'DB_URL' => '', 'DB_SSLMODE' => 'prefer'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.testing');
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$connection = $app->make('db')->connection();
if ($app->configurationIsCached() || ! $app->environment('testing') || $connection->getDriverName() !== 'pgsql'
    || $connection->getConfig('host') !== '127.0.0.1' || $connection->getConfig('database') !== 'theseguro_dev') {
    throw new RuntimeException('Reconstrução permitida somente em testing/pgsql/127.0.0.1/theseguro_dev, sem config cache.');
}
Http::preventStrayRequests();
echo "Destino: PostgreSQL 127.0.0.1:5432 / theseguro_dev (testes descartáveis)\n";
$exitCode = $kernel->call('migrate:fresh', ['--seed' => true, '--force' => true]);
echo $kernel->output();
exit($exitCode);
