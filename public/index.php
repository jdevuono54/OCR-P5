<?php

use App\Utils\Router\Router;

require __DIR__.'/../vendor/autoload.php';

// On crée le router
$router = new Router();

// On charge les routes du projet
$router->loadRoutesFromYaml(__DIR__.'/../config/routes.yaml');

$router->run();