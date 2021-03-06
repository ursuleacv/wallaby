<?php

namespace Wallaby\Base;

class View
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $layout;

    /**
     * @var string
     */
    public $theme;

    /**
     * @var string
     */
    public $pageUri;

    /**
     * @var array path to css and js files
     */
    private $layoutFiles = [];

    /**
     * @var array path to css and js files
     */
    private $viewScripts = [];

    /**
     *
     * @return void
     */
    public function __construct()
    {
        defined('PUBLIC_DIR') or define('PUBLIC_DIR', 'public');

        $this->layoutFiles = require_once ROOT . '/config/content.php';
    }

    /**
     * Render
     *
     * @param string path folder/file
     * @param array data
     * @return void
     */
    public function render($viewPath, $data = null)
    {
        if ($this->theme) {
            $viewFile = ROOT . '/' . PUBLIC_DIR . '/themes/' . $this->theme . '/views/' . $viewPath . '.php'; // path to theme view
            $layoutFile = ROOT . '/' . PUBLIC_DIR . '/themes/' . $this->theme . '/views/' . $this->layout . '.php'; // path to theme layout
        } else {
            $viewFile = ROOT . '/App/Views/' . $viewPath . '.php'; // path to view
            $layoutFile = ROOT . '/App/Views/' . $this->layout . '.php'; // path to layout
        }

        // send view to $content
        ob_start();

        // extract data
        if (is_array($data)) {
            extract($data);
        }

        if (is_file($viewFile)) {

            require_once $viewFile;

        } else {

            echo '<p>View <b>' . $viewFile . '</b> Not Found!</p>';

        }

        $content = ob_get_clean();

        if (is_file($layoutFile)) {

            $content = $this->cutScripts($content);

            require_once $layoutFile;

        } else {

            echo '<p>Layout <b>' . $layoutFile . '</b> not found!</p>';

        }
    }

    /**
     * Render Partial
     *
     * @param string path folder/file
     * @param array data
     * @return void
     */
    public function renderPartial($viewPath, $data = null, $return = false)
    {
        if ($this->theme) {
            $viewFile = ROOT . '/' . PUBLIC_DIR . '/themes/' . $this->theme . '/views/' . $viewPath . '.php'; // path to theme view
        } else {
            $viewFile = ROOT . '/App/Views/' . $viewPath . '.php'; // path to view
        }

        // extract data
        if (is_array($data)) {
            extract($data);
        }

        if ($return) {
            ob_start();
            ob_implicit_flush(false);
            require ($viewFile);
            return ob_get_clean();
        }

        if (is_file($viewFile)) {
            require $viewFile;
        } else {
            echo '<p>View <b>' . $viewFile . '</b> Not Found!</p>';
        }
    }

    /**
     *
     * $this->staticContent
     *
     * @return void
     */
    private function getLayoutStyles()
    {
        if (isset($this->layoutFiles['css'])) {

            foreach ($this->layoutFiles['css'] as $file) {

                echo '<link rel="stylesheet" type="text/css" href="/content/css/' . $file . '">';

            }
        }
    }

    /**
     *
     * $this->staticContent
     *
     * @return void
     */
    private function getLayoutScripts()
    {
        if (isset($this->layoutFiles['js'])) {

            foreach ($this->layoutFiles['js'] as $file) {

                echo '<script src="/content/js/' . $file . '"></script>';

            }
        }
    }

    /**
     *
     * $this->staticContent
     *
     * @return void
     */
    private function getViewScripts()
    {
        if (isset($this->viewScripts[0])) {

            foreach ($this->viewScripts[0] as $file) {

                echo $file;

            }
        }
    }

    /**
     *
     *
     * @param string
     * @return string
     */
    private function cutScripts($content)
    {
        $pattern = '#<script.*?>.*?</script>#si';

        preg_match_all($pattern, $content, $this->viewScripts);

        if (isset($this->viewScripts)) {

            $content = preg_replace($pattern, '', $content);

        }
        return $content;
    }

    /**
     * Renders the content to be inserted in the head section.
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderHeadHtml()
    {
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        echo empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Registers a CSS code block.
     * @param string $css the content of the CSS code block to be registered
     * @param array $options the HTML attributes for the `<style>`-tag.
     * @param string $key the key that identifies the CSS code block. If null, it will use
     * $css as the key. If two CSS code blocks are registered with the same key, the latter
     * will overwrite the former.
     */
    public function registerCss($css, $options = [], $key = null)
    {
        $key = $key ?: md5($css);
        $this->css[$key] = '<style type="text/css">' . $css . '</style>';
    }
}
