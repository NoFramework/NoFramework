NoFramework
===========
(PHP 5 >= 5.4)

NoFramework. No problem.

- vim friendly
- PSR-0 file autoloading
- dependency injection
- totally avoid globals and defines
- lazy object creation
- magic memoization
- immutable objects
- hierarchical configuration

How to use
===========

Minimal:
```php
<?php
namespace NoFramework;

require __DIR__ . '/path/to/NoFramework/Autoload.php';

(new Autoload)->register();

(new Factory([
    'namespace' => __NAMESPACE__,
    'app' => ['$new' => [
        'class' => 'Application',
        'log' => ['$new' => [
            'class' => 'Log\Output'
        ]]
    ]]
]))->app->start(function ($app) {
    $app->log->write('ok');
});
```

Extended with yaml config (require pecl yaml):
```php
<?php
require __DIR__ . '/path/to/NoFramework/Config.php';
NoFramework\Config::random_name(__FILE__, __COMPILER_HALT_OFFSET__);

class Standard
{
    protected $magic;

    public function getMagic()
    {
        return $this->magic;
    }
}

class Magic
{
    use \NoFramework\MagicProperties;

    protected function __property_memo()
    {
        return sprintf('I am default, but calculated: %d', mt_rand(0, 100));
    }
}

class Application extends \NoFramework\Application
{
    protected $log;
    protected $autoload;
    protected $period;
    protected $lazy_config_path;
    protected $lazy_read;
    protected $injected_object;

    protected function main()
    {
        file_put_contents($this->lazy_config_path, yaml_emit([
            'rand' => mt_rand()
        ]));

        $this->log->output
        -> write(print_r($this, true))
        -> write(print_r($this->autoload, true))
        #-> write(print_r($this, true))
        -> write($this->period)
        -> write(print_r($this->lazy_read, true))
        #-> write(print_r($this, true))
        -> write($this->reused)
        #-> write(print_r($this, true))
        -> write($this->injected_object->getMagic()->memo)
        -> write(print_r($this, true));

        $this->log->file
        -> write($this->injected_object->getMagic()->memo);
    }
}

NoFramework\Factory::random_name()->application->start();

__halt_compiler();

namespace: NoFramework

ini_set: !ini_set
  display_errors: 1
  display_startup_errors: 1

timezone: !setTimezone UTC

autoload: !autoloadRegister
  - NoFramework

error_handler: !errorHandlerRegister

application: !new
  class: \Application
  log: !new
    output: !new Log\Output
    file: !new
      class: Log\File
      path: !script_path test.log
  autoload: !reuse autoload
  period: !period 1y 2m 3d t 4h 5m 6s
  lazy_config_path: !script_path test_lazy.yaml
  lazy_read: !read test_lazy.yaml
  injected_object: !new
    class: \Standard
    magic: !new
      class: \Magic
      #memo: Try to uncomment me
  reused: !reuse a.b.c

a: !new
  b: !new
    c: Any object hierarchy

```

Visit http://noframework.com for more information.

