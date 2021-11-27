<?php

namespace App\Utils\Superglobals;

class Superglobals
{

    /**
     * Permet de récupérer la variable super globale SERVER
     *
     * @param $key
     *
     * @return mixed
     */
    public static function server($key = null)
    {
        return $_SERVER[$key] ?? null;
    }

    /**
     * Permet de récupérer la variable super globale SESSION
     *
     * @param $key
     *
     * @return mixed
     */
    public static function session($key = null)
    {
        if(!$key){
            return $_SESSION;
        }

        return $_SESSION[$key] ?? null;
    }

    public static function setSession($key, $value){
        $_SESSION[$key] = $value;
    }

    /**
     * Permet de récupérer la variable super globale FILES
     *
     * @param $key
     *
     * @return mixed
     */
    public static function files($key = null)
    {
        return $_FILES[$key] ?? null;
    }


    /**
     * Permet d'unset la variable de session
     */
    public static function unsetSESSION()
    {
        session_unset();
    }

    /**
     * Permet de vérifier si la variable de session existe
     */
    public static function checkSESSION($key = null){
        if(!$key){
            return isset($_SESSION);
        }

        return isset($_SESSION[$key]);
    }
}