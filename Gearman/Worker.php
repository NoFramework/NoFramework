<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Gearman;

class Worker extends \NoFramework\Service\Application
{
    protected $run = [];

    protected function __property_gearman()
    {
        return new Wrapper;
    }

    protected function main()
    {
        foreach ((array)$this->run as $run) {
            $this->log('Add: ' . $run);

            $this->gearman->addMethod(
                $run,
                function ($argument) use ($run) {
                    try {
                        return $this->use->$run->main($argument);

                    } catch (\Exception $e) {
                        $this->error_log($e);
                    } 
                }
            );
        }

        while (true) {
            try {
                $this->gearman->work();

            } catch (Stop $e) {
                break;
            }

            parent::main();
        }
    }
}

