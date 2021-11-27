<?php

use App\Utils\Orm\ConnectionFactory;
use App\Utils\Router\Router;
use App\Utils\Superglobals\Superglobals;

require '../vendor/autoload.php';

if(!Superglobals::checkSESSION()){
    session_start();
}

date_default_timezone_set('Europe/Paris');

$conf = parse_ini_file("../config/config.ini",true);

ConnectionFactory::makeConnection($conf["database"]);

// On crée le router
$router = new Router();

$router->run();