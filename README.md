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
- only 24 not very fat classes


How to use
===========

```php
<?php
require __DIR__ . '/../class.php/NoFramework/Config.php';

// Read comments in Config.php for yaml examples
// example.yaml should reside in '.config' up from script path
// (i.e. '../.config' or '../../.config', and so on until found)

(new NoFramework\Config)->parse('example.yaml')->application->start();

```

```php
<?php
require __DIR__ . '/../class.php/NoFramework/Autoload.php';

(new NoFramework\Autoload)->register();

(new NoFramework\Autoload([
    'namespace' => 'Example',
    'path' => __DIR__ . '/../class.php/Example',
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

Copy NoFramework and Twig into /home/example.com/class.php directory:
```
git clone https://github.com/NoFramework/NoFramework
git clone https://github.com/fabpot/Twig
```

Create /home/example.com/.cache and make it writable for php-fpm user or for all

Create /home/example.com/index.php:
```php
<?php
namespace NoFramework;

$debug = true;

ini_set('date.timezone', 'UTC');

ini_set('display_startup_errors', $debug);
ini_set('display_errors', $debug);

require __DIR__ . '/class.php/NoFramework/Autoload.php';

(new Autoload)->register();

(new Autoload([
    'namespace' => 'Twig',
    'path' => __DIR__ . '/class.php/Twig/lib/Twig',
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

Edit /etc/hosts on server and set ip of example.com explicitly.

Create /home/example.com/nginx.include:

```nginx
server {
    listen example.com;
    server_name www.example.com;
    return 301 $scheme://example.com$request_uri;
}

server {
    listen example.com;
    server_name example.com;
    root /home/$host;

    location = /favicon.ico {
        empty_gif;
    }

    location / {
        fastcgi_pass unix:/var/run/php5-fpm.sock; #debian default
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }
}
```

```
ln -s /home/example.com/nginx.include /etc/nginx/conf.d/example.com.conf
```

Put some html in:
```
/home/example.com/template/landing/index.html.twig

/home/example.com/template/landing/some_page.html.twig
/home/example.com/template/landing/some_dir/some_other_page.html.twig
... and so on
```

Restart nginx

Edit /etc/hosts on your local machine if necessary

Visit:
```
http://example.com/

http://example.com/some_page/
http://example.com/some_dir/some_other_page/
... and so on
```

