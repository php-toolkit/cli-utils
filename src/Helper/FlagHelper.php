<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Helper;

use function array_map;
use function explode;
use function implode;
use function is_numeric;
use function ltrim;
use function strlen;

/**
 * class FlagHelper
 */
class FlagHelper extends CliHelper
{
    /**
     * @param array $names
     *
     * @return string
     */
    public static function buildOptHelpName(array $names): string
    {
        $nodes = array_map(static function (string $name) {
            return (strlen($name) > 1 ? '--' : '-') . $name;
        }, $names);

        return implode(', ', $nodes);
    }

    /**
     * check input is valid option value
     *
     * @param string|bool $val
     *
     * @return bool
     */
    public static function isOptionValue(string|bool $val): bool
    {
        if ($val === false) {
            return false;
        }

        // if is '', 0 || is not option name
        if (!$val || $val[0] !== '-') {
            return true;
        }

        // is option name.
        if (ltrim($val, '-')) {
            return false;
        }

        // ensure is option value.
        if (!str_contains($val, '=')) {
            return true;
        }

        // is string value, but contains '='
        [$name,] = explode('=', $val, 2);

        // named argument OR invalid: 'some = string'
        return false === self::isValidName($name);
    }

    /**
     * check and get option name
     *
     * valid:
     * `-a`
     * `-b=value`
     * `--long`
     * `--long=value1`
     *
     * invalid:
     * - empty string
     * - no prefix '-' (is argument)
     * - invalid option name as argument. eg: '-9' '--34' '- '
     *
     * @param string $val
     *
     * @return string
     */
    public static function filterOptionName(string $val): string
    {
        // is not an option.
        if ('' === $val || $val[0] !== '-') {
            return '';
        }

        $name = ltrim($val, '- ');
        if (is_numeric($name)) {
            return '';
        }

        return $name;
    }

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     *
     * **NOTICE**: this is a very simple implements, recommend use package: toolkit/pflag
     *
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
     *
     * [$args, $sOpts, $lOpts] = FlagHelper::parseArgv($argv);
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
     * Supports args style:
     *
     * ```bash
     * <value>
     * arg=<value>
     * ```
     *
     * @link http://php.net/manual/zh/function.getopt.php#83414
     *
     * @param array $params
     * @param array{boolOpts:array, arrayOpts:array, mergeOpts: bool} $config
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

            // check is an option name.
            if ($pn = self::filterOptionName($p)) {
                $value  = true;
                $isLong = false;
                $option = substr($p, 1);

                // long-opt: (--<opt>)
                if (str_starts_with($option, '-')) {
                    $option = $pn;
                    $isLong = true;

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (str_contains($option, '=')) {
                        [$option, $value] = explode('=', $option, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (isset($option[1]) && $option[1] === '=') {
                    [$option, $value] = explode('=', $option, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxt = current($params);

                // next elem is value. fix: allow empty string ''
                if ($value === true && !isset($boolOpts[$option]) && self::isOptionValue($nxt)) {
                    $value = $nxt;
                    next($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($option) as $char) {
                        $sOpts[$char] = true;
                    }
                    continue;
                }

                $value   = self::filterBool($value);
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
        if (str_contains($p, '=')) {
            [$name, $value] = explode('=', $p, 2);

            if (self::isValidName($name)) {
                $args[$name] = self::filterBool($value);
            } else {
                $args[] = $p;
            }
        } else {
            $args[] = $p;
        }
    }

}
