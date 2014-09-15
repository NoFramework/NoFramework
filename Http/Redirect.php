<?php

/*
 * This file is part of the NoFramework package.
 *
 * (c) Roman Zaykin <roman@noframework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NoFramework\Http;

class Redirect extends \Exception
{
    public function __construct ($location, $code = 302) {
        parent::__construct($location, $code);
    }

    public function getLocation()
    {
        return parent::getMessage();
    }
}

