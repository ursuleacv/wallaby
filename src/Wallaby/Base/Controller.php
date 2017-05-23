<?php

namespace Wallaby\Base;

abstract class Controller
{
    /**
     * @var string
     */
    protected $layout;

    /**
     * @var string
     */
    protected $theme;

    /**
     * @var string
     */
    protected $title;

    /**
     * If ajax
     *
     * @return boolean
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Render
     *
     * @param string $viewPath
     * @param array $data
     * @return void
     */
    public function render($viewPath, $data = null)
    {
        $view = new View();

        $view->theme = $this->theme;

        $view->layout = $this->layout;

        $view->title = isset($this->title) ? $this->title : null;

        $view->render($viewPath, $data);
    }

    /**
     * Redirect
     *
     * @param string $path
     * @return void
     */
    public function redirect($path)
    {
        $path = ltrim($path, '/');

        header('Location: /' . $path);
    }
}
