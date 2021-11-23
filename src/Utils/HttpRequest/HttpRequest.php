<?php

namespace App\Utils\HttpRequest;

use App\Utils\Superglobals\Superglobals;
use Exception;

/**
 * Classe qui permet de voir différentes informations de la requête http
 */
class HttpRequest
{
    private $scriptName;
    private string $root;
    private array $get;
    private $method;
    private array $post;
    private $uri = null;
    private $baseUri = null;

    public function __construct()
    {
        $this->scriptName = Superglobals::server("SCRIPT_NAME");
        $this->root = dirname(Superglobals::server("SCRIPT_NAME"));
        $this->method = Superglobals::server("REQUEST_METHOD");
        $this->get = $_GET ?? null;
        $this->post = $_POST ?? null;

        if(Superglobals::server("REQUEST_URI")){
            $this->uri = Superglobals::server('REQUEST_URI');
            $this->baseUri = strtok(Superglobals::server("REQUEST_URI"), '?');
        }
    }

    /**
     * Getter magique
     *
     * @throws Exception
     */
    public function __get($attrName) {
        if (property_exists( $this, $attrName)){
            return $this->$attrName;
        }

        throw new Exception(__CLASS__ . ": unknown member $attrName (__get)");
    }
}