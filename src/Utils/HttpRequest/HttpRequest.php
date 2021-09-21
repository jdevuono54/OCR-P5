<?php

namespace App\Utils\HttpRequest;

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
    private $uri = [];

    public function __construct()
    {
        $this->scriptName = $_SERVER["SCRIPT_NAME"];
        $this->root = dirname($_SERVER["SCRIPT_NAME"]);
        $this->method = $_SERVER["REQUEST_METHOD"];
        $this->get = $_GET;
        $this->post = $_POST;

        if(isset($_SERVER["REQUEST_URI"])){
            $this->uri = $_SERVER['REQUEST_URI'];
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