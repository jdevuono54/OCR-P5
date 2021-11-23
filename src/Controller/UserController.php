<?php

namespace App\Controller;

use App\Exception\ImageValidationException;
use App\Model\Comment;
use App\Model\Post;
use App\Model\User;
use App\Utils\Controller\Controller;
use App\Utils\Image\ImageValidator;
use App\Utils\Superglobals\Superglobals;

class UserController extends Controller
{
    /**
     * Affiche un profil
     *
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     *
     * @throws \Twig\Error\SyntaxError
     */
    public function showProfile(){
        $uriExploded = explode('/', $this->http->uri);
        $uid = array_pop($uriExploded);

        // On récup l'user
        $user = User::first(['id', '=', $uid], ['id', 'username', 'is_valid', 'id_role', 'picture'], false);

        // S'il n'existe pas on renvoi sur la page par défaut
        if(!$user){
            $this->router->executeRoute('default', ['notify' => ['warning' => 'Utilisateur non trouvé']]);
        }

        // On récup le nombre d'articles postés par l'user et le nombre de ses commentaires valides
        $articlesCount = Post::find([['id_user', '=', $uid]], ['COUNT(*) AS COUNT'], false, 0, 0, [])[0]['COUNT'];
        $commentsCount = Comment::find([['id_user', '=', $uid], ['is_valid', '=', '1']], ['COUNT(*) AS COUNT'], false, 0, 0, [])[0]['COUNT'];

        $user['role_name'] = $user['id_role'] == 2 ? 'Membre' : 'Administrateur';
        $user['post_count'] = $articlesCount;
        $user['comments_count'] = $commentsCount;

        return $this->twigResponse('user/profile.html.twig', ['footer' => false, 'user' => $user]);
    }

    /**
     * Permet d'edit un profil
     *
     * @return int
     */
    public function editProfile(){
        header('Content-Type: application/json; charset=utf-8');

        // On check si l'image est valide sinon on renvoi une erreur
        try {
            ImageValidator::validate(Superglobals::files("image"));
        } catch (ImageValidationException $e){
            return print_r(['error' => true, 'message' => 'Image invalide : ' . $e->getMessage()]);
        }

        // On récup l'id de l'user
        $uriExploded = explode('/', $this->http->uri);
        end($uriExploded);
        $uidUser = prev($uriExploded);

        // Si l'id de l'user connecté ne correspond pas a celui du commentaire on renvoi une erreur
        if($uidUser != Superglobals::session('id')){
            return print_r(['error' => true, 'message' => 'Vous ne pouvez pas éditer l\'image de quelqu\'un d\'autre']);
        }

        // On récup l'user
        $user = User::first(['id', '=', $uidUser]);

        // Si l'user n'existe pas on renvoi une erreur
        if($user == null){
            return print_r(['error' => true, 'message' => 'Utilisateur non trouvé']);
        }

        try {
            // On stock l'image en webp avec un id unique
            $imageName = uniqid().'.webp';
            imagewebp(imagecreatefromstring(file_get_contents(Superglobals::files("image")["tmp_name"])), Superglobals::server("DOCUMENT_ROOT").'/../public/upload/profile-picture/'.$imageName);

            // On update l'user
            $user->picture = $imageName;
            Superglobals::setSession('picture', $imageName);

            $user->update();
        } catch (\Exception $e) {
            return print_r(['error' => true, 'message' => 'Erreur :' . $e->getMessage()]);
        }

        return print_r(['error' => false, 'message' => 'Image modifié avec succès']);
    }
}