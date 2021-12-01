<?php declare(strict_types=1);


namespace Toolkit\Cli\Util;

/**
 * Class Keyboard
 *
 * @package Toolkit\Cli\Util
 */
class Keyboard
{
    /**
     * @var self|null
     */
    private static ?Keyboard $global = null;

    /**
     * @return static
     */
    public static function global(): self
    {
        if (!self::$global) {
            self::$global = new self;
        }

        return self::$global;
    }

    /**
     * @return static
     */
    public static function new(): self
    {
        return new self;
    }

}