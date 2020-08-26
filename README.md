# wallaby
A simple lightweight MVC framework for building small apps in PHP.

Built in support for layouts, themes, routing.

# Usage

Create composer.json file

```json
{
    "require": {
        "php": ">=5.6.0",
        "ext-json": "*",
        "ext-pdo": "*",
        "ursuleacv/wallaby": "dev-master"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

Run `composer install` 

Create a project with the following structure
```
app
    Controllers
        BaseController.php
        HomeController.php
    Models
        User.php
config
    router.php
    app.php
public
    themes
        default
            views
                home
                    index.php
                    login.php
                layouts
                    main.php
        beta
            views
    index.php
server.php
```

Example BaseController.php
```php
<?php

namespace App\Controllers;

use Wallaby\Base\Controller;

class BaseController extends Controller
{
    /**
     *
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->theme = 'default';
        $this->layout = 'layouts/main';
    }
}
```

Example HomeController.php

```php
<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    /**
     *
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->theme = 'default'; // You can override the theme
        $this->layout = 'layouts/main';
    }

    /**
     * @return void
     */
    public function autoLogin()
    {
        //
    }
}
```

public/index.php

```php
<?php
date_default_timezone_set('UTC');
require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/../server.php';
```

server.php

```php
<?php

use Wallaby\Router;

error_reporting(-1);

defined('ROOT') or define('ROOT', __DIR__);
defined('PUBLIC_DIR') or define('PUBLIC_DIR', 'public');

$config = require_once __DIR__ . '/config/app.php';

$debug = isset($config['debug']) ? $config['debug'] : false;

defined('APP_DEBUG') or define('APP_DEBUG', $debug);

$url = trim($_SERVER['REQUEST_URI'], '/');

$configRouter = require_once ROOT . '/config/router.php';

$router = new Router($configRouter);

$router->start($url);
```

config/app.php

```php
<?php
    return [
        'appName' => 'My App Name',
        'appBaseUrl' => 'http://localhost', // no trailing slash
        'theme' => 'default',
        'version' => 'v1.0.0',
    ];
```

config/router.php

this will automatically match all routes with the following format

controller/action/param1/param2

controller/action/?param1=value1&param2=value2

Ex: 

http:localhost/site/register

http:localhost/site/contact

http:localhost/product/edit/123


```php
<?php
    return [
        'baseAction' => 'index',
        'baseController' => 'home',
        'errorHandler' => 'home/error',
        'routes' => '^(?<controller>[a-z-A-Z]+)?/?(?<action>[a-z-A-Z]+)?/?(?<parameter>.*[a-z0-9/-])?/?(?<query>\?.*)?$',
    ];
```
