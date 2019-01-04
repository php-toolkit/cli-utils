<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/1
 * Time: 下午5:33
 */

namespace Toolkit\Cli;

/**
 * Class Cli
 * @package Toolkit\Cli
 */
class Cli
{
    /*******************************************************************************
     * read/write message
     ******************************************************************************/

    /**
     * @param mixed $message
     * @param bool  $nl
     * @return string
     */
    public static function read($message = null, $nl = false): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        return trim(fgets(STDIN));
    }

    /**
     * write message to console
     * @param      $messages
     * @param bool $nl
     * @param bool $quit
     */
    public static function write($messages, $nl = true, $quit = false)
    {
        if (\is_array($messages)) {
            $messages = implode($nl ? PHP_EOL : '', $messages);
        }

        self::stdout(Color::parseTag($messages), $nl, $quit);
    }

    /**
     * Logs data to stdout
     * @param string   $message
     * @param bool     $nl
     * @param bool|int $quit
     */
    public static function stdout(string $message, $nl = true, $quit = false)
    {
        fwrite(\STDOUT, $message . ($nl ? PHP_EOL : ''));
        fflush(\STDOUT);

        if (($isTrue = true === $quit) || \is_int($quit)) {
            $code = $isTrue ? 0 : $quit;
            exit($code);
        }
    }

    /**
     * Logs data to stderr
     * @param string   $message
     * @param bool     $nl
     * @param bool|int $quit
     */
    public static function stderr(string $message, $nl = true, $quit = -1)
    {
        fwrite(\STDERR, self::color('[ERROR] ', 'red') . $message . ($nl ? PHP_EOL : ''));
        fflush(\STDOUT);

        if (($isTrue = true === $quit) || \is_int($quit)) {
            $code = $isTrue ? 0 : $quit;
            exit($code);
        }
    }

    /*******************************************************************************
     * color render
     ******************************************************************************/

    /**
     * @param                  $text
     * @param string|int|array $style
     * @return string
     */
    public static function color(string $text, $style = null): string
    {
        return Color::render($text, $style);
    }

    /*******************************************************************************
     * some helpers
     ******************************************************************************/

    /**
     * @return bool
     */
    public static function supportColor(): bool
    {
        return self::isSupportColor();
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!\defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * @return bool
     */
    public static function isSupport256Color(): bool
    {
        return DIRECTORY_SEPARATOR === '/' && strpos(getenv('TERM'), '256color') !== false;
    }

    /**
     * @return bool
     */
    public static function isAnsiSupport(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') === true || getenv('ConEmuANSI') === 'ON';
        }

        return true;
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor): bool
    {
        return \function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /*******************************************************************************
     * parse $argv
     ******************************************************************************/

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     * Supports:
     * -e
     * -e <value>
     * --long-param
     * --long-param=<value>
     * --long-param <value>
     * <value>
     * @link https://github.com/inhere/php-console/blob/master/src/io/Input.php
     * @param array $noValues List of parameters without values
     * @param bool  $mergeOpts
     * @return array
     */
    public static function parseArgv(array $noValues = [], $mergeOpts = false): array
    {
        $params = $GLOBALS['argv'];
        reset($params);

        $args = $sOpts = $lOpts = [];
        $fullScript = implode(' ', $params);
        $script = array_shift($params);

        // each() will deprecated at 7.2 so,there use current and next instead it.
        // while (list(,$p) = each($params)) {
        while (false !== ($p = current($params))) {
            next($params);

            // is options
            if ($p{0} === '-') {
                $isLong = false;
                $opt = substr($p, 1);
                $value = true;

                // long-opt: (--<opt>)
                if ($opt{0} === '-') {
                    $isLong = true;
                    $opt = substr($opt, 1);

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (strpos($opt, '=') !== false) {
                        list($opt, $value) = explode('=', $opt, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (\strlen($opt) > 2 && $opt{1} === '=') {
                    list($opt, $value) = explode('=', $opt, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxp = current($params);

                if ($value === true && $nxp !== false && $nxp{0} !== '-' && !\in_array($opt, $noValues, true)) {
                    // list(,$value) = each($params);
                    $value = current($params);
                    next($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($opt) as $char) {
                        $sOpts[$char] = true;
                    }

                    continue;
                }

                if ($isLong) {
                    $lOpts[$opt] = $value;
                } else {
                    $sOpts[$opt] = $value;
                }

                // arguments: param doesn't belong to any option, define it is args
            } else {
                // value specified inline (<arg>=<value>)
                if (strpos($p, '=') !== false) {
                    list($name, $value) = explode('=', $p, 2);
                    $args[$name] = $value;
                } else {
                    $args[] = $p;
                }
            }
        }

        unset($params);

        if ($mergeOpts) {
            return [$fullScript, $script, $args, array_merge($sOpts, $lOpts)];
        }

        return [$fullScript, $script, $args, $sOpts, $lOpts];
    }

}
