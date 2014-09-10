NoFramework
===========
(PHP 5 >= 5.5)


- PSR-0 file autoloading
- dependency injection
- lazy object creation
- magic memoization
- hierarchical configuration
- if no class is defined, try to guess it by namespace and property name
    (since we have PSR-0)
- virtual database
- ORM


How to use
===========

```php
<?php
require __DIR__ . '/../class/NoFramework/Config.php';

// Read comments in Config.php for yaml examples
// example.yaml should reside in '.config' up from script path
// (i.e. '../.config' or '../../.config', and so on until found)

(new NoFramework\Config)->parse('example.yaml')->application->start();

```

```php
<?php
require __DIR__ . '/../class/NoFramework/Autoload.php';

(new NoFramework\Autoload)->register();

(new NoFramework\Autoload([
    'namespace' => 'Example',
    'path' => __DIR__ . '/../class/Example',
])->register();

(new Example\Factory([

    // Object of class Example\Logger will be instantiated as property 'log'
    // in object of class Example\Service instantiated in property 'service'.
    // If Example\Service does not exist, then it will be Example\Factory

    'service.log' => ['$new' => 'Logger'],

]))->service->start();

```

```php
class SomeClass
{
    use \NoFramework\Magic;

    protected $default = 'default value';

    // Memoization
    // (same as default value, but calculated)
    protected function __property_pid()
    {
        echo 'calculated' . PHP_EOL;

        return posix_getpid();
    }

    public function callMe()
    {
        var_dump($this->pid);
        var_dump($this->pid);
        var_dump($this->pid);
    }
}
```


Minimal HTTP application
===========

Suppose we create project in /home/example.com

Maybe you wish to create that user, not only a directory:
```
useradd example.com
```

Copy NoFramework and Twig into /home/example.com/class:
```
git clone https://github.com/NoFramework/NoFramework
git clone https://github.com/fabpot/Twig
```

Create /home/example.com/.cache and make it writable for nginx user or for all

Create /home/example.com/index.php:
```php
<?php
namespace NoFramework;

$debug = true;

ini_set('date.timezone', 'UTC');

ini_set('display_startup_errors', $debug);
ini_set('display_errors', $debug);

require __DIR__ . '/class/NoFramework/Autoload.php';

(new Autoload)->register();

(new Autoload([
    'namespace' => 'Twig',
    'path' => __DIR__ . '/class/Twig/lib/Twig',
    'separator' => '_',
]))->register();

(new Http\Application([
    'namespace' => __NAMESPACE__,
    'template' => ['$new' => [
        'class' => 'Template\Twig',
        'path' => __DIR__ . '/template',
        'cache' => __DIR__ . '/.cache/twig',
        'search_path' => 'landing',
        'auto_reload' => $debug,
        'debug' => $debug, // enable 'dump' function
    ]]
]))->start();

```

Create /home/example.com/nginx.include:

```nginx
server {
    listen eth0; # defined in /etc/hosts
    server_name example.com;

    location = /favicon.ico {
        root /home/$host;
    }

    location / {
        fastcgi_pass unix:/var/www/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /home/$host/index.php;
        include fastcgi_params;
    }
}

```

```
ln -s /home/example.com/nginx.include /etc/nginx/conf.d/example.com.conf
```

Create /home/example.com/favicon.ico:

Put some html in:
/home/example.com/template/landing/index.html.twig

/home/example.com/template/landing/some_page.html.twig
/home/example.com/template/landing/some_dir/some_other_page.html.twig
... and so on

Restart nginx

Edit /etc/hosts if needed

Visit:
http://example.com/

http://example.com/some_page/
http://example.com/some_dir/some_other_page/
... and so on

