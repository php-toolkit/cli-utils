<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use RuntimeException;
use Toolkit\Cli\Color\Alert;
use Toolkit\Cli\Color\ColorTag;
use Toolkit\Cli\Color\Prompt;
use Toolkit\Cli\Traits\ReadMessageTrait;
use Toolkit\Cli\Traits\WriteMessageTrait;
use function array_shift;
use function count;
use function date;
use function defined;
use function function_exists;
use function getenv;
use function implode;
use function is_array;
use function is_numeric;
use function json_encode;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function stream_isatty;
use function strtoupper;
use function substr;
use function trim;
use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;
use const STDOUT;

/**
 * Class Cli
 *
 * @package Toolkit\Cli
 *
 * @method static alert(string|array|mixed $message, string $style = 'info')
 * @method static prompt(string|array|mixed $message, string $style = 'info')
 *
 * @method static red(string ...$message) Print red color message line.
 * @method static redf(string $format, ...$args) Print red color message, use like sprintf.
 * @method static cyan(string ...$message) Print cyan color message line.
 * @method static cyanf(string $format, ...$args) Print cyan color message, use like sprintf.
 * @method static blue(string ...$message) Print blue color message line.
 * @method static green(string ...$message) Print green color message line.
 * @method static magenta(string ...$message) Print cyan color message line.
 * @method static yellow(string ...$message) Print yellow color message line.
 *
 * @method static error(string ...$message) Print error style message line.
 * @method static errorf(string $format, ...$args) Print error style message, use like sprintf.
 * @method static warn(string ...$message) Print warn style message line.
 * @method static warnf(string $format, ...$args) Print warn style message, use like sprintf.
 * @method static info(string ...$message) Print info style message line.
 * @method static infof(string $format, ...$args) Print info style message, use like sprintf.
 * @method static note(string ...$message) Print note style message line.
 * @method static notef(string $format, ...$args) Print note style message, use like sprintf.
 * @method static notice(string ...$message) Print notice style message line.
 * @method static success(string ...$message) Print success style message line.
 * @method static successf(string $format, ...$args) Print success style message, use like sprintf.
 */
class Cli
{
    use ReadMessageTrait, WriteMessageTrait;

    /**
     * @param string $method {@see Color::STYLES}
     * @param array  $args
     */
    public static function __callStatic(string $method, array $args): void
    {
        if ($method === 'alert') {
            Alert::global()->withStyle($args[1] ?? '')->println($args[0]);
            return;
        }

        if ($method === 'prompt') {
            Prompt::global()->withStyle($args[1] ?? '')->println($args[0]);
            return;
        }

        if (isset(Color::STYLES[$method])) {
            $msg = count($args) > 1 ? implode(' ', $args) : (string)$args[0];
            echo Color::render($msg, $method), "\n";
            return;
        }

        // use like sprintf
        if (str_ends_with($method, 'f') ) {
            $realName = substr($method, 0, -1);

            if (isset(Color::STYLES[$realName]) && count($args) > 1) {
                $fmt = (string)array_shift($args);
                $msg = count($args) > 1 ? sprintf($fmt, ...$args) : $fmt;
                echo Color::render($msg, $realName);
                return;
            }
        }

        throw new RuntimeException('call unknown method: ' . $method);
    }

    /**
     * Print colored message to STDOUT
     *
     * @param array|string $message
     * @param array|string $style
     */
    public static function colored(array|string $message, array|string $style = 'info'): void
    {
        $str = is_array($message) ? implode(' ', $message) : $message;

        echo Color::render($str, $style) . PHP_EOL;
    }

    /*******************************************************************************
     * color render
     ******************************************************************************/

    /**
     * @return Style
     */
    public static function style(): Style
    {
        return Style::instance();
    }

    /**
     * @param string $text
     * @param array|string|null $style
     *
     * @return string
     */
    public static function color(string $text, array|string $style = null): string
    {
        return Color::render($text, $style);
    }

    public const LOG_LEVEL2TAG = [
        'info'    => 'info',
        'warn'    => 'warning',
        'warning' => 'warning',
        'debug'   => 'cyan',
        'notice'  => 'notice',
        'error'   => 'error',
    ];

    /**
     * print log to console
     *
     * ```php
     *  [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     *  ]
     * ```
     *
     * @param string $msg
     * @param array  $data
     * @param string $type
     * @param array{writeOpts:array} $labels
     * @deprecated please use Util\Clog::log()
     */
    public static function clog(string $msg, array $data = [], string $type = 'info', array $labels = []): void
    {
        if (isset(self::LOG_LEVEL2TAG[$type])) {
            $type = ColorTag::add(strtoupper($type), self::LOG_LEVEL2TAG[$type]);
        }

        $userOpts = $writeOpt = [];
        if (isset($labels['writeOpts'])) {
            $writeOpt = $labels['writeOpts'];
            unset($labels['writeOpts']);
        }

        foreach ($labels as $n => $v) {
            if (is_numeric($n) || str_starts_with($n, '_')) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString  = $userOpts ? ' ' . implode(' ', $userOpts) : '';
        $dataString = $data ? PHP_EOL . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '';

        $msg = sprintf('%s [%s]%s %s %s', date('Y/m/d H:i:s'), $type, $optString, trim($msg), $dataString);
        self::writeln($msg, false, $writeOpt);
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
     *
     * @return boolean
     */
    public static function isSupportColor(): bool
    {
        // Follow https://no-color.org/
        if (isset($_SERVER['NO_COLOR']) || false !== getenv('NO_COLOR')) {
            return false;
        }

        // COLORTERM=truecolor
        $colorTerm = getenv('COLORTERM');
        if ('truecolor' === $colorTerm) {
            return true;
        }

        // special terminal
        $termProgram = getenv('TERM_PROGRAM');
        if ('Hyper' === $termProgram || 'Terminus' === $termProgram) {
            return true;
        }

        // fix for "Undefined constant STDOUT" error
        if (!defined('STDOUT')) {
            return false;
        }

        $stream = STDOUT;
        if (DIRECTORY_SEPARATOR === '\\') {
            return (function_exists('sapi_windows_vt100_support')
                    && @sapi_windows_vt100_support($stream))
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return self::isInteractive($stream);
    }

    /**
     * @return bool
     */
    public static function isSupport256Color(): bool
    {
        return DIRECTORY_SEPARATOR === '/' && str_contains(getenv('TERM'), '256color');
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
     *
     * @param resource|mixed $fileDescriptor
     *
     * @return boolean
     */
    public static function isInteractive(mixed $fileDescriptor): bool
    {
        // PHP 7 >= 7.2.0
        if (function_exists('stream_isatty')) {
            return stream_isatty($fileDescriptor);
        }

        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * clear Ansi Code
     *
     * @param string $string
     *
     * @return string
     */
    public static function stripAnsiCode(string $string): string
    {
        return preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }
}
