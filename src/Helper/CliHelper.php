<?php declare(strict_types=1);

namespace Toolkit\Cli\Helper;

use function escapeshellarg;
use function explode;
use function is_bool;
use function is_numeric;
use function preg_match;
use function stripos;

/**
 * class CliHelper
 *
 * @author inhere
 */
class CliHelper
{
    // These words will be as a Boolean value
    public const TRUE_WORDS  = '|on|yes|true|';
    public const FALSE_WORDS = '|off|no|false|';

    /**
     * @param string $val
     *
     * @return bool
     * @deprecated please use {@see \Toolkit\Stdlib\Str::toBool2()}
     */
    public static function str2bool(string $val): bool
    {
        // check it is a bool value.
        if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
            return true;
        }

        if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
            return false;
        }

        // TODO throws error
        return false;
    }

    /**
     * @param string|bool|int|mixed $val
     *
     * @return mixed
     */
    public static function filterBool(mixed $val): mixed
    {
        if (is_bool($val) || is_numeric($val)) {
            return $val;
        }

        // check it is a bool value.
        if (false !== stripos(self::TRUE_WORDS, "|$val|")) {
            return true;
        }

        if (false !== stripos(self::FALSE_WORDS, "|$val|")) {
            return false;
        }

        return $val;
    }

    /**
     * check input is valid option value
     *
     * @param mixed $val
     *
     * @return bool
     */
    public static function isOptionValue(mixed $val): bool
    {
        if ($val === false) {
            return false;
        }

        // if is: '', 0
        if (!$val) {
            return true;
        }

        // is not option name.
        if ($val[0] !== '-') {
            // ensure is option value.
            if (!str_contains($val, '=')) {
                return true;
            }

            // is string value, but contains '='
            [$name,] = explode('=', $val, 2);

            // named argument OR invalid: 'some = string'
            return false === self::isValidName($name);
        }

        // is option name.
        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isValidName(string $name): bool
    {
        return preg_match('#^[a-zA-Z_][\w-]{0,36}$#', $name) === 1;
    }

    /**
     * Escapes a token through escape shell arg if it contains unsafe chars.
     *
     * @param string $token
     *
     * @return string
     */
    public static function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}
