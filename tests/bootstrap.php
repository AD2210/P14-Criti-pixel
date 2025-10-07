<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

/**
 * Termine toutes les connexions actives à la base de données de test
 */
function terminateTestDatabaseConnections(): void
{
    // Récupère les infos depuis DATABASE_URL
    $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
    $urlParts = parse_url($databaseUrl);
    
    if (!$urlParts) {
        return;
    }
    
    $host = $urlParts['host'] ?? 'localhost';
    $port = $urlParts['port'] ?? 5432;
    $user = $urlParts['user'] ?? 'postgres';
    $pass = $urlParts['pass'] ?? '';
    $dbName = trim($urlParts['path'] ?? '', '/');
    
    if (!$dbName) {
        return;
    }
    
    // Commande psql pour terminer les connexions
    $query = sprintf(
        "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '%s' AND pid <> pg_backend_pid();",
        $dbName
    );
    
    $cmd = sprintf(
        'PGPASSWORD=%s psql -h %s -p %d -U %s -d postgres -c %s 2>/dev/null',
        escapeshellarg($pass),
        escapeshellarg($host),
        $port,
        escapeshellarg($user),
        escapeshellarg($query)
    );
    
    passthru($cmd);
}

// create up-to-date TEST database before all tests start
terminateTestDatabaseConnections();
passthru('php bin/console doctrine:database:drop --env=test --force --if-exists');
passthru('php bin/console doctrine:database:create --env=test');
passthru('php bin/console doctrine:migrations:migrate --env=test -n');
passthru('php bin/console doctrine:fixtures:load --env=test -n');

// remove TEST database after all tests end
register_shutdown_function(function () {
    terminateTestDatabaseConnections();
    passthru('php bin/console doctrine:database:drop --env=test --force --if-exists');
});

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
