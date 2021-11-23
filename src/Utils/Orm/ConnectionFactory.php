<?php

namespace App\Utils\Orm;

use Exception;
use PDO;
use PDOException;

class ConnectionFactory
{
    private static $pdo;

    /**
     * Permet d'effectuer la connexion à la base de données
     *
     * @param array $conf
     *
     * @return int|PDO
     */
    public static function makeConnection(array $conf): PDO
    {
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false];

        try {
            self::$pdo = new PDO('mysql:host='.$conf["host"].';dbname='.$conf["dbname"].'', $conf["user"], $conf["pass"], $options);
        } catch (PDOException $e) {
            dd("Erreur !: " . $e->getMessage() . "<br/>");
        }
        return self::$pdo;
    }

    /**
     * Permet de récupérer la connexion
     *
     * @throws Exception
     */
    public static function getConnection(){
        if(self::$pdo == null){
            throw new Exception("Il faut configurer la connexion");
        }

        return self::$pdo;
    }
}