NoFramework
===========
(PHP 5 >= 5.5)

NoFramework. No problem.

- vim friendly
- PSR-0 file autoloading
- dependency injection
- totally avoid globals and defines
- no $this recursion (clear print_r)
- lazy object creation
- magic memoization
- immutable objects
- hierarchical configuration
- can be used inside or/and in parallel whith any legacy code or framework


How to use
===========


Real life (require pecl yaml):
```php
<?php
require '/path/to/NoFramework/Config.php';
NoFramework\Config::front()->application->start();
```
The code above searches '.config/NoFramework/front.yaml' up from
dirname(realpath($_SERVER['SCRIPT_FILENAME']))
and starts configured application.


The same, hiding static:
```php
<?php
require '/path/to/NoFramework/Config.php';
(new NoFramework\Config)->withFile('front.yaml', function ($state) {
    (new NoFramework\Factory($state))->application->start();
});
```


Configure for use inside legacy code.
Put somewhere in the beginning:
```php
require '/path/to/NoFramework/Config.php';
NoFramework\Config::main();
```
Use as singleton anywhere inside legacy code:
```php
\NoFramework\Factory::main()->object1->object2->...->someMethod();
```


Minimal without yaml:
```php
<?php
namespace NoFramework;

require '/path/to/NoFramework/Autoload.php';

(new Autoload)->register();

(new Factory([
    'namespace' => __NAMESPACE__,
    'app' => ['$new' => [
        'class' => 'Application',
        'log' => ['$new' => 'Log\Output']
    ]]
]))->app->start(function ($app) {
    $app->log->write('ok');
});
```


Dynamic injection and reuse:
```php
<?php
namespace NoFramework;

require '/path/to/NoFramework/Autoload.php';

(new Autoload)->register();

(new Factory([
    'namespace' => __NAMESPACE__,
    'auto' => true,
    'log' => ['$new' => [
        'output' => ['$new' => 'Log\Output']
    ]]
]))->with(function ($root) {
    # try this
    #$root->log->newInstance('Log\Nil', 'output');

    $root->newInstance([
        'class' => 'Application',
        'log' => ['$reuse' => 'log.output'],
        'magic_factory' => ['$reuse' => 'name1.name2.name3']
    ], 'app')->start(function ($app) {
        $app->log->write(print_r($app, true));
        $app->log->write(print_r($app->magic_factory, true));
    });
});
```


Tried to show some cases (require pecl yaml):
```php
<?php
require '/path/to/NoFramework/Config.php';
NoFramework\Config::random_name(__FILE__, __COMPILER_HALT_OFFSET__);

// Any class is a module for NoFramework
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

    protected function __property_rand(&$ttl)
    {
        $ttl = 1; // seconds, float or int
        return mt_rand(0, 100);
    }

    # 5.5
    #protected function __property_emitter()
    #{
    #    while (true) {
    #        yield mt_rand(0, 100);
    #    }
    #}

    # 5.5
    #protected function __property_acceptor()
    #{
    #    foreach (yield as $rand) {
    #        echo $rand . PHP_EOL;
    #    }
    #}
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

        while (true) {
            $this->log->output
            -> write($this->injected_object->getMagic()->rand);
        }

        # 5.5
        #$m = $this->injected_object->getMagic();
        #$m->acceptor->send($m->emitter);
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
  lazy_config_path: !config_path test_lazy.yaml
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

