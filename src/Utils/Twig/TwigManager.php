<?php

namespace App\Utils\Twig;

use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class TwigManager
{
    private Environment $twig;

    public function __construct()
    {
        $this->twig = new Environment(new FilesystemLoader('../ressources/templates'), [
            'cache' => false,
            'debug' => true
        ]);

        $this->twig->addGlobal('session', $_SESSION);
        $this->twig->addGlobal('uri', $_SERVER['REQUEST_URI']);
        $this->twig->addExtension(new DebugExtension());
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * @return BodyRenderer
     */
    public function getBodyRenderer(): BodyRenderer
    {
        return new BodyRenderer($this->twig);
    }

}