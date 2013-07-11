NoFramework
===========

- php >=5.4
- easy to understand
- PSR-0 file autoloading
- automatic dependency injection without recursive links
- lazy object creation
- magic memoization
- immutable objects
- clear abstractions
- hierarchical configuration
- near to lisp flexibility

Visit http://noframework.com for more information.

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

    protected function main()
    {
        $this->log->output->write(print_r($this, true));
        $this->log->output->write(print_r($this->autoload, true));
        $this->log->output->write(print_r($this, true));
        $this->log->file->write($this->period);
        file_put_contents($this->dynamic_config, yaml_emit(['rand' => mt_rand()]));
        $this->log->output->write(print_r($this->got_from_dynamic, true));
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
```
