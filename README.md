NoFramework
===========

NoFramework. No problem.

- php >=5.4
- PSR-0 file autoloading
- dependency injection
- lazy object creation
- magic memoization
- immutable objects
- hierarchical configuration

Visit http://noframework.com for more information.

Require:
- pecl yaml

How to use:

```php
<?php
require __DIR__ . '/path/to/NoFramework/Config.php';
NoFramework\Config::random_name(__FILE__, __COMPILER_HALT_OFFSET__);

class Application extends \NoFramework\Application
{
    protected $log;
    protected $autoload;
    protected $period;
    protected $dynamic_config_path;
    protected $got_from_dynamic;

    protected function __property_magic_memo()
    {
        return sprintf('I am default, but calculated: %d', mt_rand(0, 100));
    }

    protected function main()
    {
        file_put_contents($this->dynamic_config, yaml_emit(['rand' => mt_rand()]));

        $this->log->output
        -> write(print_r($this, true))
        -> write(print_r($this->autoload, true))
        -> write(print_r($this, true))
        -> write($this->period)
        -> write(print_r($this->got_from_dynamic, true))
        -> write($this->reused)
        -> write($this->magic_memo);

        $this->log->file
        -> write($this->magic_memo);
    }
}

NoFramework\Factory::random_name()->application->start();

__halt_compiler();

ini_set: !ini_set
  display_errors: 1
  display_startup_errors: 1

timezone: !setTimezone UTC

autoload: !autoloadRegister
  - NoFramework

error_handler: !errorHandlerRegister

application: !new
  class: Application
  log: !new
    output: !new NoFramework\Log\Output
    file: !new
      class: NoFramework\Log\File
      path: !script_path test.log
  autoload: !reuse autoload
  period: !period 1y 2m 3d t 4h 5m 6s
  dynamic_config: !script_path test_dynamic.yaml
  got_from_dynamic: !read test_dynamic.yaml
  reused: !reuse a.b.c
  #magic_memo: Try to uncomment me

a: !new
  b: !new
    c: Any hierarchy
```

