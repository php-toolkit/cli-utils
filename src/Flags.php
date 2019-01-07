<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-08
 * Time: 00:25
 */

namespace Toolkit\Cli;

/**
 * Class FlagsParse - console argument and option parse
 * @package Toolkit\Cli
 */
class Flags
{
    // These words will be as a Boolean value
    private const TRUE_WORDS  = '|on|yes|true|';
    private const FALSE_WORDS = '|off|no|false|';

    /**
     * @param array $argv
     * @return array [$args, $opts]
     */
    public static function simpleParseArgv(array $argv): array
    {
        $args = $opts = [];
        foreach ($argv as $key => $value) {
            // opts
            if (\strpos($value, '-') === 0) {
                $value = \trim($value, '-');

                // invalid
                if (!$value) {
                    continue;
                }

                if (\strpos($value, '=')) {
                    list($n, $v) = \explode('=', $value);
                    $opts[$n] = $v;
                } else {
                    $opts[$value] = true;
                }
            } elseif (\strpos($value, '=')) {
                list($n, $v) = \explode('=', $value);
                $args[$n] = $v;
            } else {
                $args[] = $value;
            }
        }

        return [$args, $opts];
    }

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     * eg:
     *
     * ```
     * php cli.php run name=john city=chengdu -s=test --page=23 -d -rf --debug --task=off -y=false -D -e dev -v vvv
     * ```
     *
     * ```php
     * $argv = $_SERVER['argv'];
     * // notice: must shift first element.
     * $script = \array_shift($argv);
     * $result = Flags::parseArgv($argv);
     * ```
     *
     * Supports args:
     * <value>
     * arg=<value>
     * Supports opts:
     * -e
     * -e <value>
     * -e=<value>
     * --long-opt
     * --long-opt <value>
     * --long-opt=<value>
     * @link http://php.net/manual/zh/function.getopt.php#83414
     * @param array $params
     * @param array $config
     * @return array [args, short-opts, long-opts]
     */
    public static function parseArgv(array $params, array $config = []): array
    {
        if (!$params) {
            return [[], [], []];
        }

        $config = \array_merge([
            // List of parameters without values(bool option keys)
            'noValues'       => [], // ['debug', 'h']
            // Whether merge short-opts and long-opts
            // 'mergeOpts'      => false,
            // want parsed options. if not empty, will ignore no matched
            'wantParsedOpts' => [],
            // list of params allow array.
            'arrayValues'    => [], // ['names', 'status']
        ], $config);

        $args = $sOpts = $lOpts = [];
        // config
        $noValues  = \array_flip((array)$config['noValues']);
        $arrValues = \array_flip((array)$config['arrayValues']);

        // each() will deprecated at 7.2. so,there use current and next instead it.
        // while (list(,$p) = each($params)) {
        while (false !== ($p = \current($params))) {
            \next($params);

            // is options
            if ($p{0} === '-') {
                $val    = true;
                $opt    = \substr($p, 1);
                $isLong = false;

                // long-opt: (--<opt>)
                if (\strpos($opt, '-') === 0) {
                    $opt    = \substr($opt, 1);
                    $isLong = true;

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (\strpos($opt, '=') !== false) {
                        list($opt, $val) = \explode('=', $opt, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (isset($opt{1}) && $opt{1} === '=') {
                    list($opt, $val) = \explode('=', $opt, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxt = \current($params);

                // next elem is value. fix: allow empty string ''
                if ($val === true && !isset($noValues[$opt]) && self::nextIsValue($nxt)) {
                    // list(,$val) = each($params);
                    $val = $nxt;
                    \next($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $val === true) {
                    foreach (\str_split($opt) as $char) {
                        $sOpts[$char] = true;
                    }
                    continue;
                }

                $val     = self::filterBool($val);
                $isArray = isset($arrValues[$opt]);

                if ($isLong) {
                    if ($isArray) {
                        $lOpts[$opt][] = $val;
                    } else {
                        $lOpts[$opt] = $val;
                    }
                } elseif ($isArray) { // short
                    $sOpts[$opt][] = $val;
                } else { // short
                    $sOpts[$opt] = $val;
                }
                // arguments: param doesn't belong to any option, define it is args
            } else {
                // value specified inline (<arg>=<value>)
                if (\strpos($p, '=') !== false) {
                    list($name, $val) = \explode('=', $p, 2);
                    $args[$name] = self::filterBool($val);
                } else {
                    $args[] = $p;
                }
            }
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * parse custom array params
     * ```php
     * $result = Flags::parseArray([
     *  'arg' => 'val',
     *  '--lp' => 'val2',
     *  '--s' => 'val3',
     * ]);
     * ```
     * @param array $params
     * @return array
     */
    public static function parseArray(array $params): array
    {
        $args = $sOpts = $lOpts = [];

        foreach ($params as $key => $val) {
            if ($key === '--' || $key === '-') {
                continue;
            }

            if (0 === \strpos($key, '--')) {
                $lOpts[substr($key, 2)] = $val;
            } elseif (\strpos($key, '-') === 0) {
                $sOpts[\substr($key, 1)] = $val;
            } else {
                $args[$key] = $val;
            }
        }

        return [$args, $sOpts, $lOpts];
    }

    /**
     * ```php
     * $result = Flags::parseString('foo --bar="foobar"');
     * ```
     * @todo ...
     * @param string $string
     */
    public static function parseString(string $string)
    {

    }

    /**
     * @param string|bool $val
     * @param bool        $enable
     * @return bool|mixed
     */
    public static function filterBool($val, $enable = true)
    {
        if ($enable) {
            if (\is_bool($val) || \is_numeric($val)) {
                return $val;
            }

            // check it is a bool value.
            if (false !== \stripos(self::TRUE_WORDS, "|$val|")) {
                return true;
            }

            if (false !== \stripos(self::FALSE_WORDS, "|$val|")) {
                return false;
            }
        }

        return $val;
    }

    /**
     * @param mixed $val
     * @return bool
     */
    public static function nextIsValue(string $val): bool
    {
        // current() fetch error, will return FALSE
        if ($val === false) {
            return false;
        }

        // if is: '', 0
        if (!$val) {
            return true;
        }

        // it isn't option or named argument
        return $val{0} !== '-' && false === \strpos($val, '=');
    }

    /**
     * Escapes a token through escapeshellarg if it contains unsafe chars.
     * @param string $token
     * @return string
     */
    public static function escapeToken(string $token): string
    {
        return \preg_match('{^[\w-]+$}', $token) ? $token : \escapeshellarg($token);
    }
}
