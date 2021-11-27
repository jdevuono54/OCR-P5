<?php

namespace App\Utils\Controller;

use App\Utils\HttpRequest\HttpRequest;
use App\Utils\Router\Router;
use App\Utils\Twig\TwigManager;
use Twig\Environment;

abstract class Controller
{
    protected Environment $twig;
    protected HttpRequest $http;
    protected Router $router;

    public function __construct()
    {
        $this->http = new HttpRequest();
        $this->router = new Router();

        self::initTwig();
    }

    /**
     * Permet d'initialiser Twig
     */
    private function initTwig()
    {
        $twigManager = new TwigManager();

        $this->twig = $twigManager->getTwig();
    }

    /**
     * Permet d'afficher un template twig
     *
     * @param $template
     * @param $options
     *
     * @return bool
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function twigResponse($template, $options){
        $this->twig->display($template, array_merge($options, $this->http->get, $this->http->post));

        return true;
    }
}