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
require '/path/to/NoFramework/Config.php';
NoFramework\Config::random_name(__FILE__, __COMPILER_HALT_OFFSET__);

class Application extends \NoFramework\Application
{
    protected $log;
    protected $autoload;

    protected function main()
    {
        $this->log->write(print_r($this, true));
        $this->log->write(print_r($this->autoload, true));
        $this->log->write(print_r($this, true));
    }
}

NoFramework\Factory::random_name()->application->start();

__halt_compiler();

autoload: !autoloadRegister
application: !new
  class: Application
  log: !new NoFramework\Log\Output
  autoload: !reuse autoload
```

