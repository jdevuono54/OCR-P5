<?php

namespace App\Utils\Router;

use App\Security\Authentification;
use App\Utils\Superglobals\Superglobals;
use App\Utils\Twig\TwigManager;
use InvalidArgumentException;

use App\Utils\HttpRequest\HttpRequest;
use Symfony\Component\Yaml\Yaml;

class Router
{
    private ?HttpRequest $httpRequest;
    private $auth;

    private array $routes = [];

    public function __construct($file = null){
        // Si on ne passe pas un chemin perso on récup les routes dans config
        if($file == null){
            $file = Superglobals::server('DOCUMENT_ROOT').'/../config/routes.yaml';
        }

        $this->httpRequest = new HttpRequest();
        $this->auth = new Authentification();

        // On load les routes
        $this->loadRoutesFromYaml($file);
    }

    /**
     * Permet de set les routes grâce au fichier yaml
     *
     * @param $file
     */
    public function loadRoutesFromYaml($file){
        // Si le fichier n'est pas présent on soulève un erreur
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('The file %s not exists!', $file));
        }

        // On parse le fichier
        $this->routes = Yaml::parse(file_get_contents($file), Yaml::PARSE_CONSTANT);
    }

    /**
     * Permet de run le routeur
     *
     * @param null $options
     *
     * @return bool|mixed
     */
    public function run($options = null)
    {
        // On récup la route ciblé & l'alias de la route
        $requestedRoute = $this->httpRequest->baseUri;
        $routeAlias = $this->getRouteAlias($requestedRoute);

        // Si l'alias n'existe pas on affiche la 404
        if (!$routeAlias) {
            return $this->showErrorWebpage(404);
        }

        // on vérifie que l'utilisateur à les droits
        if (!$this->auth->checkAccessRight($this->routes[$routeAlias][2])) {

            // Si il n'a pas les droits on show la page de 403
            return $this->showErrorWebpage(403);
        }

        // On explode la méthode et le controller
        $exploded = explode('.', $this->routes[$routeAlias][1]);

        // On récup le controller et la méthode
        $controller = $exploded[0];
        $method = $exploded[1];

        // On instancie le controller & on appelle la méthode
        $wantedController = new $controller();
        return $wantedController->$method($options);
    }

    /**
     * Permet de récupérer l'alias d'une route
     *
     * @param $requestedRoute
     * @return false|int|string
     */
    private function getRouteAlias($requestedRoute){
        // On explode la route ciblée
        $requestExplode = explode('/', rtrim($requestedRoute, "/"));

        // Pour chaque route
        foreach($this->routes as $alias => $route)
        {
            $match = true;

            // On explode la route qu'on check
            $routeToCompareExploded = explode('/', rtrim($route[0], "/"));

            // Si on a pas le même nombre d'éléments on passe a la route suivante
            if(count($routeToCompareExploded) != count($requestExplode)){
                continue;
            }

            // Pour chaque morceau de l'url
            foreach ($requestExplode as $key => $portion){

                // Si la route contient un param format {id} on ne compare pas
                if(strpos($routeToCompareExploded[$key], '{') !== false){
                    continue;
                }

                // Si la portion de l'url cible & celle qu'on analyse ne corresponde pas on indique que ça ne match pas
                if($portion != $routeToCompareExploded[$key]){
                    $match = false;
                }
            }

            // On regarde si le lien correspond au lien cible & on retourne l'alias si c'est le cas
            if($match)
            {
                return $alias;
            }
        }

        return false;
    }

    /**
     * Permet de construire un lien à partir d'un alias et d'une liste de param
     *
     * @param $alias
     * @param array $paramList
     * @return false|string
     *
     * @throws \Exception
     */
    public function getUrl($alias, array $paramList = [])
    {
        // Si l'alias n'existe pas
        if(!key_exists($alias, $this->routes)){
            throw new \Exception("L'alias n'existe pas");
        }

        // On récup l'url
        $url = $this->httpRequest->scriptName . $this->routes[$alias][0];

        // Si il n'y a pas de param on retourne l'url
        if ($paramList == null) {
            return $url;
        }

        // Sinon on construit l'url avec les params
        $url = $url . "?";

        foreach ($paramList as $key => $value) {
            $url = $url . $key . "=" . $value . "&";
        }

        return substr($url, 0, -1);
    }

    /**
     * Permet d'éxecuter une route
     *
     * @param $alias
     * @param null $options
     * @param int $code
     */
    public function executeRoute($alias, $options= null, $code = 200){
        // Si l'alias existe on vérifie que l'utilisateur à les droits
        if (!$this->auth->checkAccessRight($this->routes[$alias][2])) {
            // Si il n'a pas les droits on se prépare pour le redirigé vers la route par default
            $alias = "default";
        }

        // On ajoute les params
        $query = $options ? http_build_query($options) : null;

        // On redirige
        header('Location:' . $this->routes[$alias][0] . '?' . $query, $code);
    }

    /**
     * Permet d'afficher une page d'erreur
     *
     * @param $code
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function showErrorWebpage($code){
        http_response_code($code);

        // On load twig
        $twigManager = new TwigManager();
        $twig = $twigManager->getTwig();

        // On display la page grace a son code
        $twig->display('error/'.$code.'.html.twig');

        return true;
    }
}