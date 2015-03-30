<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Model;

trait Modify
{
    use \NoFramework\Magic;

    protected function __property__id() {}

    public function modify($command = [])
    {
        $collection = $this->{'$$collection'}->current();

        $command['query']['_id'] = $this->_id ?: ['$exists' => false];
        $command['new'] = true;
        $command += [
            'upsert' => !$this->_id,
            'set' => [],
            'unset' => [],
            'ensure' => [],
            'is_save' => true,
        ];

        $command['unset'] += array_filter($command['set'], function ($value) {
            return is_null($value);
        });

        if ($command['unset']) {
            $command['unset'] = array_fill_keys(array_keys($command['unset']), null);
            $command['set'] = array_diff_key($command['set'], $command['unset']);

            $collection->setState($this, array_intersect_key(
                (new \ReflectionClass($this))->getDefaultProperties() + $command['unset'],
                $command['unset']
            ));

            $command['unset'] = array_diff_key($command['unset'], $command['ensure']);
        }

        if ($command['set']) {
            $collection->setState($this, $command['set']);
            $command['ensure'] = array_diff_key($command['ensure'], $command['set']);
        }

        foreach ($command['ensure'] as $property => $ignored) {
            $command['set'][$property] = $this->$property;
        }

        if ($command['is_save']) {
            if (!$command['unset']) {
                unset($command['unset']);
            }

            if (!$command['set']) {
                unset($command['set']);
            }

            unset($command['ensure']);
            unset($command['is_save']);

            $result = $collection->findAndModify($command);

            if (isset($result->value)) {
                $collection->setState($this, $result->value);
            }

            return $result;
        }

        return false;
    }

    public function __set($name, $value)
    {
        if (
            '_id' === $name or
            !$this->modify(['set' => [$name => $value]])
        ) {
            trigger_error(sprintf('Cannot set property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }

    public function __unset($name)
    {
        if (
            '_id' === $name or
            !$this->modify(['unset' => [$name => 1]])
        ) {
            trigger_error(sprintf('Cannot unset property %s::$%s',
                static::class,
                $name
            ), E_USER_ERROR);
        }
    }
}

