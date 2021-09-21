<?php

namespace App\Utils\Router;

use App\Security\Authentification;
use InvalidArgumentException;

use App\Utils\HttpRequest\HttpRequest;
use Symfony\Component\Yaml\Yaml;

class Router
{
    private ?HttpRequest $httpRequest;

    private array $routes = [];

    public function __construct(){
        $this->httpRequest = new HttpRequest();
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
     * @return mixed
     */
    public function run()
    {
        // On récup la route ciblé & l'alias de la route
        $requestedRoute = $this->httpRequest->uri;
        $routeAlias = $this->getRouteAlias($requestedRoute);

        // Si l'alias existe on vérifie que l'utilisateur à les droits
        if ($routeAlias) {
            $auth = new Authentification();

            if (!$auth->checkAccessRight($this->routes[$routeAlias][2])) {
                // Si il n'a pas les droits on se prépare pour le redirigé vers la route par default
                $routeAlias = "default";
            }
        } else {
            // Si l'alias n'existe pas on se prépare pour le redirigé vers la route par default
            $routeAlias = "default";
        }

        // On explode la méthode et le controller
        $explodeMethodAndController = explode('.', $this->routes[$routeAlias][1]);

        // On récup le controller et la méthode
        $controller = $explodeMethodAndController[0];
        $method = $explodeMethodAndController[1];

        // On instancie le controller & on appelle la méthode
        $wantedController = new $controller();
        return $wantedController->$method();
    }

    /**
     * Permet de récupérer l'alias d'une route
     *
     * @param $requestedRoute
     * @return false|int|string
     */
    private function getRouteAlias($requestedRoute){
        // Pour chaque route
        foreach($this->routes as $alias => $route)
        {
            // On regarde si le lien correspond au lien cible & on retourne l'alias si c'est le cas
            if($route[0] == $requestedRoute)
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
        // Si l'alias existe
        if (key_exists($alias, $this->routes)) {
            // On récup l'url
            $url = $this->httpRequest->scriptName . $this->routes[$alias][0];

            // Si il n'y a pas de param on retourne l'url
            if ($paramList == null) {
                return $url;
            } else { // Sinon on construit l'url avec les params
                $url = $url . "?";

                foreach ($paramList as $key => $value) {
                    $url = $url . $key . "=" . $value . "&";
                }

                return substr($url, 0, -1);
            }
        } else {
            throw new \Exception("L'alias n'existe pas");
        }
    }
}