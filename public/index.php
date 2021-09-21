<?php

use App\Utils\Orm\ConnectionFactory;
use App\Utils\Router\Router;

require __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('Europe/Paris');

$conf = parse_ini_file("../config/config.ini",true);

ConnectionFactory::makeConnection($conf["database"]);

// On crée le router
$router = new Router();

// On charge les routes du projet
$router->loadRoutesFromYaml(__DIR__.'/../config/routes.yaml');

$router->run();