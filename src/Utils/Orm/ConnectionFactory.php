<?php

namespace App\Utils\Orm;

use Exception;
use PDO;

class ConnectionFactory
{
    private static $pdo;

    /**
     * Permet d'effectuer la connexion à la base de données
     *
     * @param array $conf
     *
     * @return PDO
     */
    public static function makeConnection(array $conf): PDO
    {
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false];

        self::$pdo = new PDO('mysql:host='.$conf["host"].';dbname='.$conf["dbname"].'', $conf["user"], $conf["pass"], $options);
        return self::$pdo;
    }

    /**
     * Permet de récupérer la connexion
     *
     * @throws Exception
     */
    public static function getConnection(){
        if(self::$pdo != null){
            return self::$pdo;
        }else{
            throw new Exception("Il faut configurer la connexion");
        }
    }
}