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
    passthru('php bin/console doctrine:database:drop --env=test --force --if-exists');
});

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
