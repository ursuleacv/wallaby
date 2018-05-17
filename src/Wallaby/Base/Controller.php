<?php

namespace Wallaby\Base;

use Exception;

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

    public function __construct()
    {
        $this->initSystemHandlers();
    }

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
    public function renderPartial($viewPath, $data = null, $return = false)
    {
        $view = new View();

        $view->theme = $this->theme;

        $view->layout = null;

        $view->title = isset($this->title) ? $this->title : null;

        $view->pageUri = isset($this->pageUri) ? $this->pageUri : null;

        if ($return) {
            return $view->renderPartial($viewPath, $data, $return);
        }

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

    public function wantsJson()
    {
        return self::contains($this->getHeader('Content-Type'), ['/json', '+json']);
    }

    public function getHeader($key, $default = null)
    {
        $headers = $this->getHeaders();

        $headers = array_change_key_case($headers, CASE_UPPER);

        $key = strtoupper($key);

        return isset($headers[$key]) ? $headers[$key] : $default;
    }

    public function getHeaders()
    {
        if (!function_exists('getallheaders')) {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $headers = getallheaders();

        return $headers;
    }

    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function initSystemHandlers()
    {
        defined('APP_DEBUG') or define('APP_DEBUG', false);
        defined('APP_WANTS_JSON') or define('APP_WANTS_JSON', false);

        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'), error_reporting());
    }

    /**
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler. It requires
     * that constant APP_DEBUG be defined true.
     *
     * The application will be terminated by this method.
     *
     * @param Exception $exception exception that is not caught
     */
    public function handleException($exception)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        restore_exception_handler();

        $this->displayException($exception);
    }

    /**
     * Handles PHP execution errors such as warnings, notices.
     *
     * This method is implemented as a PHP error handler. It requires
     * that constant APP_DEBUG be defined true.
     *
     * The application will be terminated by this method.
     *
     * @param integer $code the level of the error raised
     * @param string $message the error message
     * @param string $file the filename that the error was raised in
     * @param integer $line the line number the error was raised at
     */
    public function handleError($code, $message, $file, $line)
    {
        if ($code & error_reporting()) {
            // disable error capturing to avoid recursive errors
            restore_error_handler();
            restore_exception_handler();

            $log = "$message ($file:$line)\nStack trace:\n";
            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }

            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }

                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }

                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }

                $log .= "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $log .= get_class($t['object']) . '->';
                }

                $log .= "{$t['function']}()\n";
            }
            if (isset($_SERVER['REQUEST_URI'])) {
                $log .= 'REQUEST_URI=' . $_SERVER['REQUEST_URI'];
            }

            if (class_exists('Monolog\\Logger')) {
                $logger = new \Monolog\Logger('system');
                $logger->pushHandler(new \Monolog\Handler\StreamHandler(
                    ROOT . '/storage/system_error.log', \Monolog\Logger::WARNING)
                );
                $logger->error($log);
            }

            try {
                $this->displayError($code, $message, $file, $line);
            } catch (Exception $e) {
                $this->displayException($e);
            }

            try {
                exit(1);
            } catch (Exception $e) {
                // use the most primitive way to log error
                $msg = get_class($e) . ': ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                $msg .= $e->getTraceAsString() . "\n";
                $msg .= "Previous error:\n";
                $msg .= $log . "\n";
                $msg .= '$_SERVER=' . var_export($_SERVER, true);
                error_log($msg);
                exit(1);
            }
        }
    }

    /**
     * Displays the captured PHP error.
     * This method displays the error in HTML when there is
     * no active error handler.
     * @param integer $code error code
     * @param string $message error message
     * @param string $file error file
     * @param string $line error line
     */
    public function displayError($code, $message, $file, $line)
    {
        if (!headers_sent()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        }

        $output = '';

        if (APP_DEBUG) {
            $output = "<h1>Error [$code]</h1>\n";
            $output .= "<p>$message ($file:$line)</p>\n";
            $output .= '<pre>';

            $trace = debug_backtrace();
            // skip the first 3 stacks as they do not tell the error position
            if (count($trace) > 3) {
                $trace = array_slice($trace, 3);
            }

            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }

                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }

                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }

                $output .= "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $output .= get_class($t['object']) . '->';
                }

                $output .= "{$t['function']}()\n";
            }

            $output .= '</pre>';
        } else {
            $output .= "<h1>Error [$code]</h1>\n";
            $output .= "<p>$message</p>\n";
        }

        if (APP_WANTS_JSON && $this->wantsJson()) {
            echo json_encode([
                'error' => [
                    'message' => $message,
                    'code' => $code,
                ],
            ]);
        } else {
            echo $output;
        }
    }

    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception $exception the uncaught exception
     */
    public function displayException($exception)
    {
        $code = $exception->getCode() > 0 ? $exception->getCode() : 500;
        if (!headers_sent()) {
            http_response_code($code);
        }

        $output = '';

        if (APP_DEBUG) {
            $output = '<h1>' . get_class($exception) . "</h1>\n";
            $output .= '<p>' . $exception->getMessage() . ' (' . $exception->getFile() . ':' . $exception->getLine() . ')</p>';
            $output .= '<pre>' . $exception->getTraceAsString() . '</pre>';
        } else {
            $output .= '<h1>' . get_class($exception) . "</h1>\n";
            $output .= '<p>' . $exception->getMessage() . '</p>';
        }

        if (class_exists('Monolog\\Logger')) {
            $logger = new \Monolog\Logger('system');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler(
                ROOT . '/storage/system_error.log', \Monolog\Logger::WARNING)
            );
            $logger->error($this->jTraceExeption($exception));
            $logger->error($exception->getMessage() . "\n" . $this->getExceptionTraceAsString($exception));
        }

        if (APP_WANTS_JSON && $this->wantsJson()) {
            echo json_encode([
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => $code,
                ],
            ]);
        } else {
            echo $output;
        }
    }

    protected function getExceptionTraceAsString($exception)
    {
        $rtn = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $file = isset($frame['file']) ? $frame['file'] : '';
            $line = isset($frame['line']) ? $frame['line'] : '';
            $func = isset($frame['function']) ? $frame['function'] : '';

            $rtn .= sprintf("#%s %s(%s): %s(%s)\n",
                $count,
                $file,
                $line,
                $func,
                $args);
            $count++;
        }
        return $rtn;
    }

    /**
     * jTraceEx() - provide a Java style exception trace
     * @param $exception
     * @param $seen      - array passed to recursive calls to accumulate trace lines already seen
     *                     leave as NULL when calling this function
     * @return array of strings, one entry per trace line
     */
    protected function jTraceExeption($e, $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result = array();
        if (!$seen) {
            $seen = array();
        }

        $trace = $e->getTrace();
        $prev = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();
        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace) + 1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line);
            if (is_array($seen)) {
                $seen[] = "$file:$line";
            }

            if (!count($trace)) {
                break;
            }

            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join("\n", $result);
        if ($prev) {
            $result .= "\n" . jTraceEx($prev, $seen);
        }

        return $result;
    }
}
