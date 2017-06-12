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
     * @var string
     */
    protected $pageUri;

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

        $view->pageUri = isset($this->pageUri) ? $this->pageUri : null;

        $view->render($viewPath, $data);
    }

    /**
     * Render Partial
     *
     * @param string $viewPath
     * @param array $data
     * @return void
     */
    public function renderPartial($viewPath, $data = null)
    {
        $view = new View();

        $view->theme = $this->theme;

        $view->layout = null;

        $view->title = isset($this->title) ? $this->title : null;

        $view->pageUri = isset($this->pageUri) ? $this->pageUri : null;

        $view->renderPartial($viewPath, $data);
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
