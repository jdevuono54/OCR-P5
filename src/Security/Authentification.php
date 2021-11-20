<?php

namespace App\Security;

use App\Exception\AuthentificationException;

class Authentification
{
    // Level en fonction du rôle
    const ACCESS_LEVEL_NONE = 1;
    const ACCESS_LEVEL_USER  = 2;
    const ACCESS_LEVEL_ADMIN = 3;

    // Id de l'utilisateur connecté
    private $id = null;

    // Email de l'utilisateur connecté
    private $email = null;

    // Nom de l'utilisateur connecté
    private $username = null;

    // Accès de l'utilisateur connecté
    private $accessLevel = self::ACCESS_LEVEL_NONE;

    // Image de l'utilisateur connecté
    private $picture = null;

    public function __construct()
    {
        // Si la session existe, on set les valeurs
        if(isset($_SESSION["email"])){
            $this->id = $_SESSION["id"];
            $this->email = $_SESSION["email"];
            $this->username = $_SESSION["username"];
            $this->accessLevel = $_SESSION["accessLevel"];
            $this->picture = $_SESSION["picture"];
        }
    }

    /**
     * Permet d'update la session
     *
     * @param $username
     * @param $level
     */
    protected function updateSession($id,$email, $username, $level, $picture){
        $this->id = $id;
        $this->email = $email;
        $this->username = $username;
        $this->accessLevel = $level;
        $this->picture = $picture;

        $_SESSION['id'] = $id;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $username;
        $_SESSION['accessLevel'] = $level;
        $_SESSION['picture'] = $picture;
    }

    /**
     * Permet de log l'utilisateur si le mot de passe est bon
     *
     * @param string $email
     * @param string $username
     * @param $dbPass String Mot de passe stocké en base
     * @param $password String Password à vérifier
     * @param int $level
     *
     * @throws AuthentificationException
     */
    public function login(string $id,string $email,string $username, string $dbPass, string $password, int $level, $picture){
        // Si le mot de passe n'est pas bon on soulève un erreur
        if($this->verifyPassword($password, $dbPass) == false){
            throw new AuthentificationException("Mot de passe incorrect");
        } else{ // Sinon on update la session
            $this->updateSession($id, $email, $username, $level, $picture);
        }
    }

    /**
     * Permet de déconnecter un user
     */
    public function logout(){
        // On unset les variables de session
        unset($_SESSION['id'], $_SESSION['email'], $_SESSION['username'], $_SESSION['accessLevel'], $_SESSION['picture']);

        $this->id = null;
        $this->email = null;
        $this->username = null;
        $this->accessLevel = self::ACCESS_LEVEL_NONE;
        $this->picture = null;
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
    public static function hashPassword($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }
}