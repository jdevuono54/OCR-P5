<?php

namespace App\Controller;

use App\Exception\AuthentificationException;
use App\Exception\RegistrationFormException;
use App\Exception\UserEmailAlreadyExistException;
use App\Model\User;
use App\Security\Authentification;
use App\Utils\Controller\Controller;
use App\Utils\Mail\Mail;

class AuthentificationController extends Controller
{
    PUBLIC CONST PASSWORD_LENGTH_MIN = 6;
    PUBLIC CONST USERNAME_LENGTH_MIN = 3;

    private Authentification $auth;

    public function __construct()
    {
        parent::__construct();

        $this->auth = new Authentification();
    }

    /**
     * Route qui permet d'afficher la page de login
     */
    public function showLogin(){
        return $this->twigResponse('authentification/login.html.twig', ['footer' => false]);
    }

    /**
     * Route qui permet d'afficher la page d'inscription
     */
    public function showRegister(){
        return $this->twigResponse('authentification/register.html.twig', ['footer' => false]);
    }

    /**
     * Login d'un user
     *
     * @return bool|void
     *
     * @throws \App\Exception\AuthentificationException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function login(){
        $email = $this->http->post['email'] ?? '';
        $password = $this->http->post['password'] ?? '';

        // On récup l'user avec le mail correspondant
        $user = User::first(["email", "=", $email]);

        // Si on n'a pas de résultat on renvoi une erreur
        if($user == null){
            return $this->twigResponse('authentification/login.html.twig',
                ['footer' => false, 'notify' => ['danger' => 'Email ou mot de passe incorrect']]);
        } elseif (!$user->is_valid){ // Si le compte n'est pas valide on renvoie une erreur
            return $this->twigResponse('authentification/login.html.twig',
                ['footer' => false, 'notify' => ['warning' => "Votre compte n'a pas encore été valider"]]);
        } else { // Sinon on tente de log l'user
            try{
                $this->auth->login($user->id, $user->email, $user->username, $user->password, $password, $user->id_role, $user->picture);

                $this->router->executeRoute('default');
            } catch (AuthentificationException $e){
                return $this->twigResponse('authentification/login.html.twig',
                    ['footer' => false, 'notify' => ['danger' => 'Email ou mot de passe incorrect']]);
            }
        }
    }

    /**
     * Permet de déconnecté un user
     */
    public function logout(){
        $this->auth->logout();

        $this->router->executeRoute('default', ['notify' => ['success' => 'Vous avez été deconnecté avec succès']]);
    }

    /**
     * Permet d'inscrire un user
     *
     * @throws \App\Exception\EmptyTableNameException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function registration(){
        $username = $this->http->post['username'] ?? '';
        $email = $this->http->post['email'] ?? '';
        $password = $this->http->post['password'] ?? '';
        $confirmPassword = $this->http->post['confirmPassword'] ?? '';

        try {
            // On valide les données
            $this->registrationFormDataValidation($username, $email, $password, $confirmPassword);

            // On crée l'user
            $this->createUser($username, $email, $password);

            // On envoi le mail d'inscription
            Mail::send($email, 'Bienvenue sur le blog !', '/email/registerEmail.html.twig', ['username' => $username]);
        } catch (\Exception $e){
            // En cas d'erreur on renvoi sur le formulaire avec une erreur
            return $this->twigResponse('authentification/register.html.twig',
                ['footer' => false, 'notify' => ['danger' => $e->getMessage()]]);
        }

        $this->router->executeRoute('login', ['notify' => ['success' => 'Compte crée avec succès !']]);
    }

    /**
     * Permet de check les données du formulaire d'inscription
     *
     * @throws RegistrationFormException
     */
    private function registrationFormDataValidation($username, $email, $password, $confirmPassword){
        // On check si l'email est valide
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RegistrationFormException("L'adresse email n'est pas valide");
        }

        // On check la taille du mot de passe
        if(strlen($password) < self::PASSWORD_LENGTH_MIN){
            throw new RegistrationFormException("Le mot de passe fait moins de ".self::PASSWORD_LENGTH_MIN. " caractères");
        } elseif ($password != $confirmPassword){ // On check si le mdp et la confirm sont identiques
            throw new RegistrationFormException("Le mot de passe et la confirmation ne sont pas identiques");
        }

        // On check la taille de l'username
        if(strlen($username) < self::USERNAME_LENGTH_MIN){
            throw new RegistrationFormException("Le pseudo fait moins de ".self::USERNAME_LENGTH_MIN. " caractères");
        }
    }

    /**
     * Permet de créer un user
     *
     * @param $username
     * @param $email
     * @param $password
     *
     * @throws UserEmailAlreadyExistException
     * @throws \App\Exception\EmptyTableNameException
     */
    private function createUser($username, $email, $password){
        $check = User::first([["email", "=", $email], ["username", "=", $username]]);

        // On check si l'user existe déjà et on renvoi une erreur si c'est le cas
        if($check != null){
            throw new UserEmailAlreadyExistException("L'utilisateur existe déjà");
        }

        // On crée l'user
        $user = new User();

        $user->username = $username;
        $user->email = $email;
        $user->password = Authentification::hashPassword($password);
        $user->id_role = Authentification::ACCESS_LEVEL_USER;
        $user->is_valid = 0;

        $user->insert();
    }

    /**
     * Permet de valider un user
     */
    public function validUser(){
        $uid = $this->http->post['id'] ?? '';
        $user = User::first(["id", "=", $uid]);

        $response['error'] = true;

        // Si l'user existe on met la réponse en mode ok et on update l'user
        if($user != null){
            $response['error'] = false;

            $user->is_valid = 1;
            $user->update();
        }

        header('Content-Type: application/json; charset=utf-8');

        return print_r(json_encode($response));
    }
}