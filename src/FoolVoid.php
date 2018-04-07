<?php

namespace Foolz\Package;

/**
 * Class to pass instead of primitive types
 *
 * @author Foolz <support@foolz.us>
 * @package Foolz\Package
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License 2.0
 */
class FoolVoid
{

    /**
     * Use to set default parameters to void
     *
     * @return  \Foolz\Plugin\FoolVoid
     */
    public static function forge()
    {
        return new static();
    }
}
