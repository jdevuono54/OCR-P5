<?php

namespace App\Security;

use App\Exception\AuthentificationException;

class Authentification
{
    // Level en fonction du rôle
    const ACCESS_LEVEL_NONE = 0;
    const ACCESS_LEVEL_USER  = 1;
    const ACCESS_LEVEL_ADMIN = 2;

    // Nom de l'utilisateur connecté
    private $userLogin = null;

    // Accès de l'utilisateur connecté
    private $accessLevel = self::ACCESS_LEVEL_NONE;

    public function __construct()
    {
        // Si la session existe, on set les valeurs
        if(isset($_SESSION["userLogin"])){
            $this->userLogin = $_SESSION["userLogin"];
            $this->accessLevel = $_SESSION["accessLevel"];
        }
    }

    /**
     * Permet d'update la session
     *
     * @param $username
     * @param $level
     */
    protected function updateSession($username, $level){
        $this->userLogin = $username;
        $this->accessLevel = $level;

        $_SESSION['userLogin'] = $username;
        $_SESSION['accessLevel'] = $level;

    }

    /**
     * Permet de log l'utilisateur si le mot de passe est bon
     *
     * @param $username
     * @param $dbPass String Mot de passe stocké en base
     * @param $password String Password à vérifier
     * @param $level
     *
     * @throws AuthentificationException
     */
    public function login($username, string $dbPass, string $password, $level){
        // Si le mot de passe n'est pas bon on soulève un erreur
        if($this->verifyPassword($password, $dbPass) == false){
            throw new AuthentificationException("Mot de passe incorrect");
        } else{ // Sinon on update la session
            $this->updateSession($username,$level);
        }
    }

    /**
     * Permet de déconnecter un user
     */
    public function logout(){
        // On unset les variables de session
        unset($_SESSION['userLogin'],$_SESSION['accessLevel']);

        $this->userLogin = null;
        $this->accessLevel = self::ACCESS_LEVEL_NONE;
    }

    /**
     * Permet de vérifier si l'user a les droits nécessaires
     *
     * @param $requested
     * @return bool
     */
    public function checkAccessRight($requested){
        return $requested <= $this->accessLevel;
    }


    /**
     * Permet de vérifier de vérifier le mot de passe
     *
     * @param $password
     * @param $hash
     *
     * @return bool
     */
    private function verifyPassword($password, $hash){
        return password_verify($password,$hash);
    }

    /**
     * Permet de hash le mot de passe
     *
     * @param $password
     *
     * @return false|string|null
     */
    public function hashPassword($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }
}