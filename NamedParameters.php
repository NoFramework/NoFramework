<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework;

trait NamedParameters
{
    public function __call($name, $argument)
    {
        $input = array_shift($argument);

        while ($argument) {
            $next = array_shift($argument);

            foreach($next as $key => $value) {
                if (isset($input[$key]) and
                    is_integer($key)
                ) {
                    $input[] = $value;

                } elseif (isset($input[$key]) and
                    is_array($value) and
                    is_array($input[$key])
                ) {
                    $input[$key] = $this->__call('__call', [
                        $input[$key],
                        $value
                    ]);

                } else {
                    $input[$key] = $value;
                }
            }
        }

        if ('__call' === $name) {
            return $input;
        }

        $self = new \ReflectionClass($this);

        if ($self->hasMethod('__named_' . $name)) { 
            $method = $self->getMethod('__named_' . $name);
            $argument = [];

            foreach ($method->getParameters() as $parameter) {
                if (isset($input[$parameter->getName()])) {
                    $argument[] = $input[$parameter->getName()];
                    unset($input[$parameter->getName()]);

                } elseif ($parameter->isDefaultValueAvailable()) {
                    $argument[] = $parameter->getDefaultValue();

                } else {
                    trigger_error(sprintf(
                        'Missing argument \'%s\' for %s::%s()',
                        $parameter->getName(),
                        get_called_class(),
                        $name
                    ), E_USER_WARNING);
                }
            }

            if ($input) {
                if ($count = count($argument) and
                    is_array($input) and
                    is_array($argument[$count - 1])
                ) {
                    $argument[$count - 1] = $this->__call(
                        '__call',
                        [$argument[$count - 1], $input]
                    );
                } else {
                    $argument[] = $input; 
                }
            }

            $method->setAccessible(true);
            return $method->invokeArgs($this, $argument);

        } else {
            trigger_error(sprintf(
                'Call to undefined method %s::%s()',
                get_called_class(),
                $name
            ), E_USER_ERROR);
        }
    }
}

