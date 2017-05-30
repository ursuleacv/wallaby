<?php

namespace Wallaby;

class Router
{
    /**
     * @var array
     */
    private $route = [];

    /**
     * @var array
     */
    private $config = [];

    /**
     *
     * config/router.php
     *
     * @return void
     */
    public function __construct($config)
    {
        if (empty($config)) {
            throw new InvalidArgumentException('Required config not passed');
        }

        $this->config = $config;

        $this->config['routes'] = [$this->config['routes'] => []];
    }

    /**
     *
     *
     * @param string
     * @return void
     */
    public function start($url)
    {

        if ($this->searchRoute($url)) {

            $controllerName = 'App\Controllers\\' . $this->getNormalName($this->route['controller']) . 'Controller';
            
            if (class_exists($controllerName)) {

                $controller = new $controllerName();

                $action = 'action' . $this->getNormalName($this->route['action']);

                if (method_exists($controller, $action)) {

                    parse_str($this->getQueryString(), $queryString);

                    $parameters = array_merge($this->getParameters(), $queryString);

                    call_user_func_array([$controller, $action], $parameters);

                    return;
                }
            }
        }
        $this->handleError();
    }

    private function getQueryString()
    {
        return $q = isset($this->route['query']) ? ltrim($this->route['query'], '?') : null;
    }

    /**
     * Connect 'handleError' in config/router.php
     *
     * @return void
     */
    private function handleError()
    {
        $this->start($this->config['errorHandler']);
    }

    /**
     * Search Route
     *
     * @param string
     * @return boolean
     */
    private function searchRoute($url)
    {
        foreach ($this->config['routes'] as $pattern => $route) {

            if (preg_match("#$pattern#i", $url, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $route[$key] = $value;
                    }
                }
                if (!isset($route['controller'])) {
                    $route['controller'] = $this->config['baseController'];
                }
                if (!isset($route['action'])) {
                    $route['action'] = $this->config['baseAction'];
                }
                $this->route = $route;
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return array
     */
    private function getParameters()
    {
        if (isset($this->route['parameter'])) {

            return explode('/', $this->route['parameter']);

        }
        return [];
    }

    /**
     *
     * @param string
     * @return string
     */
    private function getNormalName($name)
    {
        $name = str_replace('-', ' ', $name);

        $name = ucwords($name);

        return str_replace(' ', '', $name);
    }
}
