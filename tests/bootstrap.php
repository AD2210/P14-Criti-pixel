<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// create up-to-date TEST database before all tests start
passthru('php bin/console doctrine:database:drop --env=test --force --if-exists');
passthru('php bin/console doctrine:database:create --env=test');
passthru('php bin/console doctrine:migrations:migrate --env=test -n');
passthru('php bin/console doctrine:fixtures:load --env=test -n');

// remove TEST database after all tests end
register_shutdown_function(function () {
    passthru('php -r "require \'vendor/autoload.php\'; (new Symfony\Component\Dotenv\Dotenv())->bootEnv(\'' . addslashes(dirname(__DIR__) . '/.env') . '\'); $url=parse_url(getenv(\'DATABASE_URL\')); $db=trim($url[\'path\'], \'/\'); $host=$url[\'host\']??\'localhost\'; $port=$url[\'port\']??5432; $user=$url[\'user\']??\'postgres\'; $pass=$url[\'pass\']??\'\'; $cmd=sprintf(\'PGPASSWORD=%s psql -h %s -p %d -U %s -d postgres -v ON_ERROR_STOP=1 -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = \'\'%s\'\' AND pid <> pg_backend_pid();"\' , escapeshellarg($pass), escapeshellarg($host), $port, escapeshellarg($user), $db); passthru($cmd);";');
    passthru('php bin/console doctrine:database:drop --env=test --force --if-exists');
});

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
