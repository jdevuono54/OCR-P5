<?php

namespace App\Controller;

use App\Exception\ImageValidationException;
use App\Model\Comment;
use App\Model\Post;
use App\Model\Role;
use App\Model\User;
use App\Utils\Controller\Controller;
use App\Utils\Image\ImageValidator;
use App\Utils\Superglobals\Superglobals;

class AdminController extends Controller
{
    /**
     * Permet d'afficher la page d'accueil
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showHome(){
        $articlesCount = Post::first([], ['COUNT(*) AS count_valid_articles'], false);
        $commentsCount = Comment::first([], ['COUNT(*) AS comments_counts', 'COUNT(DISTINCT IF(is_valid = 1, id, NULL)) AS count_valid_comments'], false);
        $usersCount = User::first([], ['COUNT(*) AS users_count', 'COUNT(DISTINCT IF(is_valid = 1, id, NULL)) AS count_valid_users'], false);

        return $this->twigResponse('admin/home.html.twig', ['footer' => false, 'counters' => array_merge($articlesCount, $commentsCount, $usersCount)]);
    }

    /**
     * Permet d'afficher la page de tt les users valides
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showUsers(){
        return $this->twigResponse('admin/showUsers.html.twig', ['footer' => false]);
    }

    /**
     * Permet de récup tt les users valides
     *
     * @throws \Exception
     */
    public function getUsers(){
        $data = [];

        // Si ne fait pas de recherche on prend tt les users valides
        if(empty($this->http->get['search'])){
            $users = User::find(['is_valid', '=', '1'], ['id', 'username', 'email', 'is_valid', 'id_role'], false);

            $data['recordsFiltered'] = User::first([['is_valid', '=', '1']], ['COUNT(*) count'], false)['count'];
        } else { // Si on fait une recherche on récup les users valides avec un username qui match la recherche
            $users = User::find(
                [
                    ['is_valid', '=', '1'],
                    ['username', 'LIKE', $this->http->get['search']['value'].'%']
                ],
                ['id', 'username', 'email', 'is_valid', 'id_role'], false
            );

            $data['recordsFiltered'] = User::first([['is_valid', '=', '1'], ['username', 'LIKE', $this->http->get['search']['value'].'%']], ['COUNT(*) count'], false)['count'];
        }

        $roles = Role::find([], [], false);

        $rolesNames = [];

        // On attribue le nom du rôle pour chaque user
        foreach ($roles as $role){
            $rolesNames[$role['id']] = $role['name'];
        }

        $data['draw'] = $this->http->get['draw'];
        $data['recordsTotal'] = count($users);

        // On met en forme les données
        foreach ($users as $user){
            $data['data'][] = [
                'username' => '<a href="/profile/'.$user['id'].'">'.$user['username'].'</a>',
                'email' => $user['email'],
                'id_role' => $rolesNames[$user['id_role']]
            ];
        }

        header('Content-Type: application/json; charset=utf-8');

        print_r(json_encode($data));
    }

    /**
     * Page qui montre les users en attente de validation
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showUsersValidation(){
        return $this->twigResponse('admin/showUsersValidation.html.twig', ['footer' => false]);
    }

    /**
     * Permet de récup les users en attente de validation
     *
     * @throws \Exception
     */
    public function getUsersValidation(){
        $data = [];

        // Si ne fait pas de recherche on prend tt les users non valide
        if(empty($this->http->get['search']['value'])){
            $users = User::find([['is_valid', '=', '0']], ['id', 'username', 'email'], false, $this->http->get['length'], $this->http->get['start'], [['updated_at', 'ASC'], ['created_at', 'ASC']]);

            $data['recordsFiltered'] = User::first([['is_valid', '=', '0']], ['COUNT(*) count'], false)['count'];
        } else { // Si on fait une recherche on récup les users non valides avec un username qui match la recherche
            $users = User::find(
                [
                    ['is_valid', '=', '0'],
                    ['username', 'LIKE', $this->http->get['search']['value'].'%']
                ],
                ['id', 'username', 'email'], false, $this->http->get['length'], $this->http->get['start'], [['updated_at', 'ASC'], ['created_at', 'ASC']]);

            $data['recordsFiltered'] = User::first([['is_valid', '=', '0'], ['username', 'LIKE', $this->http->get['search']['value'].'%']], ['COUNT(*) count'], false)['count'];
        }

        $data['draw'] = $this->http->get['draw'];
        $data['recordsTotal'] = count($users);

        // On met en forme les données
        foreach ($users as $user){
            $data['data'][] = [
                'username' => $user['username'],
                'email' => $user['email'],
                'actions' => '<div class="buttons are-small"><button class="button is-primary valid-user" data-user-id="'.$user["id"].'">Valider</button></div>'
            ];
        }

        header('Content-Type: application/json; charset=utf-8');

        print_r(json_encode($data));
    }

    /**
     * Retourne la vue qui permet d'ajoute un article
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showAddArticle(){
        return $this->twigResponse('admin/addArticle.html.twig', ['footer' => false]);
    }

    /**
     * Permet d'ajouter un article
     *
     * @return int
     */
    public function addArticle(){
        header('Content-Type: application/json; charset=utf-8');

        $title = $this->http->post['title'] ?? '';
        $content = $this->http->post['content'] ?? '';

        // Si le titre on le contenu est manquant on retourne une erreur
        if(empty($title) || empty($content)){
            echo json_encode(['error' => true, 'message' => 'Titre ou contenu manquant']);

            return 1;
        }

        // Si l'image n'est pas valide on retourne une erreur
        try {
            ImageValidator::validate(Superglobals::files("image"));
        } catch (ImageValidationException $e){
            echo json_encode(['error' => true, 'message' => 'Image invalide : ' . $e->getMessage()]);

            return 1;
        }

        try {
            // On stock l'image en webp avec un id unique
            $imageName = uniqid().'.webp';
            imagewebp(imagecreatefromstring(file_get_contents(Superglobals::files("image")["tmp_name"])), Superglobals::server("DOCUMENT_ROOT").'/../public/upload/post/'.$imageName);

            // On crée le post
            $post = new Post();

            $post->title = $this->http->post['title'];
            $post->content = $this->http->post['content'];
            $post->picture = $imageName;
            $post->id_user = Superglobals::session('id');

            $post->insert();
        } catch (\Exception $e) {
            echo json_encode(['error' => true, 'message' => 'Erreur :' . $e->getMessage()]);

            return 1;
        }

        echo json_encode(['error' => false, 'message' => 'Article créer avec succès']);
        return 1;
    }

    /**
     * Retourne la vue qui montre tt les articles
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showArticles(){
        return $this->twigResponse('admin/showArticles.html.twig', ['footer' => false]);
    }

    /**
     * Permet de récupérer les articles
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getArticles(){
        $data = [];

        // Si on ne fait pas de recherche on récup tt les articles
        if(empty($this->http->get['search']['value'])){
            $articles = Post::find(null, [], true, $this->http->get['length'], $this->http->get['start'], [['created_at', 'DESC']]);

            $data['recordsFiltered'] = Post::first([], ['COUNT(*) count'], false)['count'];
        } else { // Sinon on recherche par titre
            $articles = Post::find(
                [
                    ['title', 'LIKE', $this->http->get['search']['value'].'%']
                ],
                [], true, $this->http->get['length'], $this->http->get['start'], [['created_at', 'DESC']]);

            $data['recordsFiltered'] = Post::first([['title', 'LIKE', $this->http->get['search']['value'].'%']], ['COUNT(*) count'], false)['count'];
        }

        $data['draw'] = $this->http->get['draw'];
        $data['recordsTotal'] = count($articles);

        // On met en forme les données
        foreach ($articles as $article){
            $data['data'][] = [
                'title' => '<a href="/blog/post/'.$article->id.'">'.$article->title.'</a>',
                'picture' => '<img src="/upload/post/'.$article->picture.'" loading="lazy" height="40" width="40">',
                'author' => $article->author()->username,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'actions' => '
                    <div class="buttons are-small">
                        <a href="/admin/articles/showUpdateArticle/'.$article->id.'" class="mr-2"><button class="button is-primary edit-post">Modifier</button></a>
                        <button class="button is-danger delete-post" data-article-id="'.$article->id.'">Supprimer</button>
                    </div>'
            ];
        }

        header('Content-Type: application/json; charset=utf-8');

        print_r(json_encode($data));
    }

    /**
     * Permet de  un article
     *
     * @return int
     */
    public function deleteArticle(){
        $id = $this->http->post['id'] ?? '';

        // On récup l'article
        $post = Post::first(["id", "=", $id]);

        // S'il n'existe pas on renvoi un message d'erreur sinon on le delete
        if($post == null){
            $response['error'] = true;
            $response['message'] = 'Article non trouvé';
        } else {
            $response['error'] = false;

            Comment::where(['id_post', "=", $id])->delete();

            $post->delete();
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($response);
        return 1;
    }

    /**
     * Permet de montrer la vue pour edit un article
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showUpdateArticle(){
        $uriExploded = explode('/', $this->http->uri);
        $id = array_pop($uriExploded);

        // On récup l'article
        $post = Post::find(["id", "=", $id], [], false);

        // S'il n'existe pas on retourne une erreur
        if($post == null){
            return $this->twigResponse('admin/showArticles.html.twig', ['footer' => false, 'notify' => ['warning' => "Article non trouvé"]]);
        }

        return $this->twigResponse('admin/addArticle.html.twig', ['footer' => false, 'post' => $post[0]]);
    }

    /**
     * Permet d'update un article
     *
     * @return int
     */
    public function updateArticle(){
        header('Content-Type: application/json; charset=utf-8');

        $uriExploded = explode('/', $this->http->uri);
        $id = array_pop($uriExploded);

        // On récup l'article
        $post = Post::first(["id", "=", $id]);

        // S'il n'existe pas on retourne une erreur
        if($post == null){
            $response['error'] = true;
            $response['message'] = 'Article non trouvé';

            echo json_encode($response);
            return 1;
        }

        $title = $this->http->post['title'] ?? '';
        $content = $this->http->post['content'] ?? '';

        // Si le titre ou contenu est manquant  on retourne une erreur
        if(empty($title) || empty($content)){
            echo json_encode(['error' => true, 'message' => 'Titre ou contenu manquant']);

            return 1;
        }

        // On valide l'image sinon on retourne une erreur
        if(Superglobals::files("image")['name']){
            try {
                ImageValidator::validate(Superglobals::files("image"));
            } catch (ImageValidationException $e){
                echo json_encode(['error' => true, 'message' => 'Image invalide : ' . $e->getMessage()]);

                return 1;
            }
        }

        // On met a jour l'article
        try {
            $post->title = $this->http->post['title'];
            $post->content = $this->http->post['content'];

            if(Superglobals::files("image")['name']){
                $imageName = uniqid().'.webp';
                imagewebp(imagecreatefromstring(file_get_contents(Superglobals::files("image")["tmp_name"])), Superglobals::server("DOCUMENT_ROOT").'/../public/upload/post/'.$imageName);

                $post->picture = $imageName;
            }

            $post->update();
        } catch (\Exception $e) {
            echo json_encode(['error' => true, 'message' => 'Erreur :' . $e->getMessage()]);

            return 1;
        }

        echo json_encode(['error' => false, 'message' => 'Article modifier avec succès']);
        return 1;
    }

    /**
     * Vue qui permet de voir les articles en attente de validation
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showCommentsValidation(){
        return $this->twigResponse('admin/showCommentsValidation.html.twig', ['footer' => false]);
    }

    /**
     * Permet récup les articles en attente de validation
     *
     * @throws \Exception
     */
    public function getCommentsValidation(){
        // On récup les articles en attente de validation
        $comments = Comment::find(
            [
                ['is_valid', '=', '0']
            ],
            [], true, $this->http->get['length'], $this->http->get['start'], [['updated_at', 'ASC'], ['created_at', 'ASC']]);

        $data = [];
        $data['draw'] = $this->http->get['draw'];
        $data['recordsTotal'] = count($comments);
        $data['recordsFiltered'] = Comment::first([['is_valid', '=', '0']], ['COUNT(*) count'], false)['count'];

        // On met en forme
        foreach ($comments as $comment){
            $post = $comment->post();

            $data['data'][] = [
                'post' =>  '<a href="/blog/post/'.$post->id.'">'.$post->title.'</a>',
                'user' => $comment->author()->username,
                'comment' => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $comment->content),
                'actions' => '<div class="buttons are-small">
                                   <button class="button is-primary valid-comment" data-comment-id="'.$comment->id.'">Valider</button>
                                   <button class="button is-danger delete-comment" data-comment-id="'.$comment->id.'">Supprimer</button>
                               </div>'
            ];
        }

        header('Content-Type: application/json; charset=utf-8');

        print_r(json_encode($data));
    }
}