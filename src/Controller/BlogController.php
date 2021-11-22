<?php

namespace App\Controller;

use App\Model\Comment;
use App\Model\Post;
use App\Security\Authentification;
use App\Utils\Controller\Controller;
use App\Utils\Mail\Mail;

class BlogController extends Controller
{
    /**
     * Route qui permet d'afficher la page d'accueil du blog
     */
    public function showHomepage(){
        echo $this->twig->render('homepage.html.twig', []);
    }

    /**
     * Envoi du mail du formulaire de contact
     */
    public function contact(){
        $lastname = $this->http->post['lastname'] ?? '';
        $firstname = $this->http->post['firstname'] ?? '';
        $email = $this->http->post['email'] ?? '';
        $subject = $this->http->post['subject'] ?? '';
        $message = $this->http->post['message'] ?? '';

        // On vérifie que les données ne sont pas vide
        if(empty($lastname) || empty($firstname) || empty($email) || empty($subject) || empty($message)){
            $this->router->executeRoute('default', ['notify' => ['warning' => 'Formulaire incomplet']]);
            return 1;
        }

        // On envoi le mail
        Mail::send('jacques.devuono@gmail.com', 'Blog contact form', '/email/contactEmail.html.twig',
            [
                'lastname' => $lastname,
                'firstname' => $firstname,
                'mail' => $email,
                'subject' => $subject,
                'message' => $message
            ]);

        $this->router->executeRoute('default', ['notify' => ['success' => 'Votre email a été envoyé avec succès']]);
    }

    /**
     * Permet d'afficher la liste des articles
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showBlog(){
        $page = $this->http->get['p'] ?? 1;
        $limit = 8;
        $offset = ($page - 1) * $limit;

        // On récup les articles et on compte le nombre total d'articles
        $articlesCount = Post::find(null, ['COUNT(*) AS COUNT'], false, 0, 0, [])[0]['COUNT'];
        $articles = Post::find(null, [], true, $limit, $offset, [['created_at', 'DESC']]);

        // On calcul le nombre de pages
        $nbPages = ceil($articlesCount / $limit);

        $posts = [];

        // On formate les données
        foreach ($articles as $article){
            $posts[] = [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $this->removeJs($article->content),
                'picture' => $article->picture,
                'author' => $article->author()->username,
                'author_id' => $article->author()->id,
            ];
        }

        return $this->twigResponse('blog/showBlog.html.twig', ['posts' => $posts, 'pagination' => ['nbPages' => $nbPages, 'page' => $page]]);
    }

    /**
     * Affiche un post
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function showPost(){
        $uriExploded = explode('/', $this->http->uri);
        $id = array_pop($uriExploded);

        $page = $this->http->get['p'] ?? 1;
        $limit = 8;
        $offset = ($page - 1) * $limit;

        // On récup un post
        $post = Post::first(["id", "=", $id], []);

        // S'il n'existe pas on renvoi une erreur
        if($post == null){
            $this->router->executeRoute('blog', ['notify' => ['warning' => 'Article non trouvé']]);
        }

        // On récup l'auteur du post
        $author = $post->author();

        // Si on est admin on récup les commentaires en attente de validation de tt le monde
        if(isset($_SESSION['accessLevel']) && $_SESSION['accessLevel'] >= Authentification::ACCESS_LEVEL_ADMIN){
            $postComments = Comment::find(['id_post', '=' ,$id], [], true, $limit,$offset, []);
            $postCommentsCount = Comment::first(['id_post', '=' ,$id], ['COUNT(*) AS COUNT'], false)['COUNT'];
        } else {
            // On récup les commentaires valides
            $postComments = Comment::find([['id_post', '=' ,$id], ['is_valid', '=', '1']], [], true, $limit,$offset, []);

            // Si un user est co on récup en plus ses commentaires non valides
            if(isset($_SESSION['id'])){
                $postComments = array_merge($postComments, Comment::find([['id_post', '=' ,$id],    ['is_valid', '=', '0'], ['id_user', '=', $_SESSION['id']]], [], true, $limit,$offset, []));
                $postCommentsCount = Comment::first([['id_post', '=' ,$id], ['is_valid', '=', '0'], ['id_user', '=', $_SESSION['id']]], ['COUNT(*) AS COUNT'], false)['COUNT'];
            } else {
                $postCommentsCount = Comment::first([['id_post', '=' ,$id], ['is_valid', '=', '1']], ['COUNT(*) AS COUNT'], false)['COUNT'];
            }
        }

        $comments = [];

        // On formate les données
        foreach ($postComments as $comment){
            $commentAuthor = $comment->author(false);

            $comments[] = [
                'id' => $comment->id,
                'content' => $this->removeJs($comment->content),
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'is_valid' => $comment->is_valid,
                'author' => $commentAuthor['username'],
                'author_id' => $commentAuthor['id'],
                'author_role' => $commentAuthor['id_role'] == 2 ? 'Membre' : 'Administrateur',
                'author_picture' => $commentAuthor['picture']
            ];
        }

        // On rend le tableau unique de commentaires
        $comments = array_unique($comments, SORT_REGULAR);

        $nbPages = ceil($postCommentsCount / $limit);

        // On tri les commentaires par date de création
        usort($comments, function ($item1, $item2) {
            return $item1['created_at'] <=> $item2['created_at'];
        });

        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'picture' => $post->picture,
            'content' => $this->removeJs($post->content),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'author' => $author->username,
            'author_id' => $author->id,
            'comments' => $comments
        ];

        return $this->twigResponse('blog/showPost.html.twig', ['footer' => true, 'post' => $data, 'pagination' => ['nbPages' => $nbPages, 'page' => $page]]);
    }

    /**
     * Permet d'ajouter un commentaire
     *
     * @return int
     *
     * @throws \App\Exception\EmptyTableNameException
     */
    public function addComment(){
        header('Content-Type: application/json; charset=utf-8');

        $content = $this->http->post['content'] ?? '';

        // Si le contenu est vide on retourne une erreur
        if(empty($content)){
            echo json_encode(['error' => true, 'message' => 'Le contenu du commentaire est vide']);
            return 1;
        }

        $uriExploded = explode('/', $this->http->uri);

        end($uriExploded);
        $idPost = prev($uriExploded);

        // On récup le post
        $post = Post::first(["id", "=", $idPost], []);

        // Si le post n'existe pas on retourne une erreur
        if($post == null){
            echo json_encode(['error' => true, 'message' => 'Article non trouvé']);
            return 1;
        }

        // On crée le post
        $comment = new Comment();

        $comment->content = $content;
        $comment->id_post = $idPost;
        $comment->id_user = $_SESSION['id'];
        $comment->is_valid = 0;

        // Si l'user co est admin on valide directement le commentaire
        if($_SESSION['accessLevel'] >= Authentification::ACCESS_LEVEL_ADMIN){
            $comment->is_valid = 1;
        }

        $comment->insert();

        echo json_encode(['error' => false, 'message' => "Commentaire en attente d'approbation"]);
        return 1;
    }

    /**
     * Permet de remove le js d'une chaine de carac
     *
     * @param $content
     *
     * @return array|string|string[]|null
     */
    private function removeJs($content){
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
    }

    /**
     * Permet de valider un commentaire
     */
    public function validComment(){
        $id = $this->http->post['id'] ?? '';
        $comment = Comment::first(["id", "=", $id]);

        if($comment == null){
            $response['error'] = true;
        } else {
            $response['error'] = false;

            $comment->is_valid = 1;

            $comment->update(false);
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($response);
    }

    /**
     * Permet d'update un commentaire
     *
     * @return int|void
     */
    public function updateComment(){
        header('Content-Type: application/json; charset=utf-8');

        $id = $this->http->post['id'] ?? '';
        $content = $this->http->post['content'] ?? '';

        // Si le contenu est vide on retourne une erreur
        if(empty($content)){
            echo json_encode(['error' => true, 'message' => 'Le contenu du commentaire est vide']);
            return 1;
        }

        // On récup le commentaire
        $comment = Comment::first(["id", "=", $id]);

        // S'il n'existe pas on retourne une erreur
        if($comment == null){
            echo json_encode(['error' => true, 'message' => 'Commentaire non trouvé']);
            return 1;
        } if(($comment->id_user != $_SESSION['id'])){ // Si ce n'est pas le commentaire de l'user co on retourne une erreur
            echo json_encode(['error' => true, 'message' => 'Vous ne pouvez pas modifié ce commentaire']);
            return 1;
        }

        $comment->content = $content;
        $comment->is_valid = 0;

        $comment->update();

        echo json_encode(['error' => false, 'message' => 'Commentaire edité avec succès']);
    }

    /**
     * Permet de supprimé un commentaire
     *
     * @return int
     */
    public function deleteComment(){
        $id = $this->http->post['id'] ?? '';

        $comment = Comment::first(["id", "=", $id]);

        if($comment == null){
            $response['error'] = true;
            $response['message'] = 'Commentaire non trouvé';
        } if(($comment->id_user != $_SESSION['id']) && ($_SESSION['accessLevel'] < Authentification::ACCESS_LEVEL_ADMIN)){
            $response['error'] = true;
            $response['message'] = 'Vous ne pouvez pas supprimé ce commentaire';
        } else {
            $response['error'] = false;

            $comment->delete();
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($response);
        return 1;
    }
}