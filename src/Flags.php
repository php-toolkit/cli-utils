<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use Toolkit\Cli\Helper\FlagHelper;
use Toolkit\Cli\Util\LineParser;
use function array_flip;
use function array_merge;
use function current;
use function escapeshellarg;
use function explode;
use function is_int;
use function next;
use function preg_match;
use function str_split;
use function strpos;
use function substr;
use function trim;

/**
 * Class FlagsParse - console argument and option parse
 *
 * @package Toolkit\Cli
 */
class Flags
{
    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     * eg:
     *
     * ```bash
     * php cli.php run name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off -y=false -D -e dev -v vvv
     * ```
     *
     * Usage:
     *
     * ```php
     * $argv = $_SERVER['argv'];
     * // notice: must shift first element.
     * $script = \array_shift($argv);
     * $result = Flags::parseArgv($argv);
     * ```
     *
     * Supports args style:
     *
     * ```bash
     * <value>
     * arg=<value>
     * ```
     *
     * Supports opts style:
     *
     * ```bash
     * -e
     * -e <value>
     * -e=<value>
     * --long-opt
     * --long-opt <value>
     * --long-opt=<value>
     * ```
     *
     * @link http://php.net/manual/zh/function.getopt.php#83414
     *
     * @param array $params
     * @param array $config
     *
     * @return array returns like `[args, short-opts, long-opts]`; If 'mergeOpts' is True, will return `[args, opts]`
     */
    public static function parseArgv(array $params, array $config = []): array
    {
        if (!$params) {
            return [[], [], []];
        }

        $config = array_merge([
            // List of parameters without values(bool option keys)
            'boolOpts'       => [], // ['debug', 'h']
            // Whether merge short-opts and long-opts
            'mergeOpts'      => false,
            // Only want parsed options.
            // if not empty, will ignore no matched
            'wantParsedOpts' => [],
            // List of option allow array values.
            'arrayOpts'      => [], // ['names', 'status']
            // Special short style
            // posix: -abc will expand: -a -b -c
            // unix: -abc  will expand: -a=bc
            'shortStyle'     => 'posix',
        ], $config);

        $args = $sOpts = $lOpts = [];
        // config
        $boolOpts  = array_flip((array)$config['boolOpts']);
        $arrayOpts = array_flip((array)$config['arrayOpts']);

        $optParseEnd = false;
        while (false !== ($p = current($params))) {
            next($params);

            // option parse end, collect remaining arguments.
            if ($optParseEnd) {
                self::collectArgs($args, $p);
                continue;
            }

            // is options and not equals '-' '--'
            if ($p && $p[0] === '-' && '' !== trim($p, '-')) {
                $value  = true;
                $isLong = false;
                $option = substr($p, 1);

                // long-opt: (--<opt>)
                if (strpos($option, '-') === 0) {
                    $option = substr($option, 1);
                    $isLong = true;

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (strpos($option, '=') !== false) {
                        [$option, $value] = explode('=', $option, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (isset($option[1]) && $option[1] === '=') {
                    [$option, $value] = explode('=', $option, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxt = current($params);

                // next elem is value. fix: allow empty string ''
                if ($value === true && !isset($boolOpts[$option]) && FlagHelper::isOptionValue($nxt)) {
                    // list(,$val) = each($params);
                    $value = $nxt;
                    next($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($option) as $char) {
                        $sOpts[$char] = true;
                    }
                    continue;
                }

                $value   = FlagHelper::filterBool($value);
                $isArray = isset($arrayOpts[$option]);

                if ($isLong) {
                    if ($isArray) {
                        $lOpts[$option][] = $value;
                    } else {
                        $lOpts[$option] = $value;
                    }
                } elseif ($isArray) { // short
                    $sOpts[$option][] = $value;
                } else { // short
                    $sOpts[$option] = $value;
                }

                continue;
            }

            // stop parse options:
            // - found '--' will stop parse options
            if ($p === '--') {
                $optParseEnd = true;
                continue;
            }

            // parse arguments:
            // - param doesn't belong to any option, define it is args
            self::collectArgs($args, $p);
        }

        if ($config['mergeOpts']) {
            return [$args, array_merge($sOpts, $lOpts)];
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * @param array  $args
     * @param string $p
     */
    private static function collectArgs(array &$args, string $p): void
    {
        // value specified inline (<arg>=<value>)
        if (strpos($p, '=') !== false) {
            [$name, $value] = explode('=', $p, 2);

            if (FlagHelper::isValidName($name)) {
                $args[$name] = FlagHelper::filterBool($value);
            } else {
                $args[] = $p;
            }
        } else {
            $args[] = $p;
        }
    }

    /**
     * parse custom array params
     * ```php
     * $result = Flags::parseArray([
     *  'arg' => 'val',
     *  '--lp' => 'val2',
     *  '--s' => 'val3',
     *  '-h' => true,
     * ]);
     * ```
     *
     * @param array $params
     *
     * @return array
     */
    public static function parseArray(array $params): array
    {
        $args = $sOpts = $lOpts = [];

        foreach ($params as $key => $val) {
            if (is_int($key)) { // as argument
                $args[$key] = $val;
                continue;
            }

            $cleanKey = trim((string)$key, '-');

            if ('' === $cleanKey) { // as argument
                $args[] = $val;
                continue;
            }

            if (0 === strpos($key, '--')) { // long option
                $lOpts[$cleanKey] = $val;
            } elseif (0 === strpos($key, '-')) { // short option
                $sOpts[$cleanKey] = $val;
            } else {
                $args[$key] = $val;
            }
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * Parse flags from a string
     *
     * ```php
     * $result = Flags::parseString('foo --bar="foobar"');
     * ```
     *
     * @param string $string
     * @param array  $config
     *
     * @return array
     */
    public static function parseString(string $string, array $config = []): array
    {
        $flags = LineParser::parseIt($string);

        return self::parseArgv($flags, $config);
    }

    /**
     * @param array $argv
     *
     * @return array
     * @deprecated
     */
    public static function simpleParseArgv(array $argv): array
    {
        return $argv;
    }

    /**
     * check next is option value
     *
     * @param mixed $val
     *
     * @return bool
     * @deprecated please use FlagHelper::isOptionValue
     */
    public static function nextIsValue($val): bool
    {
        return FlagHelper::isOptionValue($val);
    }

    /**
     * @param string $name
     *
     * @return bool
     * @deprecated please use FlagHelper::isValidName
     */
    public static function isValidArgName(string $name): bool
    {
        return FlagHelper::isValidName($name);
    }

    /**
     * @param string|bool $val
     * @param bool        $enable
     *
     * @return bool|int|mixed
     * @deprecated please use FlagHelper::filterBool
     */
    public static function filterBool($val, bool $enable = true)
    {
        return FlagHelper::filterBool($val);
    }

    /**
     * Escapes a token through escape shell arg if it contains unsafe chars.
     *
     * @param string $token
     *
     * @return string
     * @deprecated please use FlagHelper::escapeToken
     */
    public static function escapeToken(string $token): string
    {
        return preg_match('{^[\w-]+$}', $token) ? $token : escapeshellarg($token);
    }
}
