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
use Toolkit\Cli\Color\Prompt;
use Toolkit\Cli\Traits\ReadMessageTrait;
use Toolkit\Cli\Traits\WriteMessageTrait;
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
use function strpos;
use function strtoupper;
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
 * @method static cyan(string ...$message) Print cyan color message line.
 * @method static blue(string ...$message) Print blue color message line.
 * @method static green(string ...$message) Print green color message line.
 * @method static magenta(string ...$message) Print cyan color message line.
 * @method static yellow(string ...$message) Print yellow color message line.
 *
 * @method static error(string ...$message) Print error style message line.
 * @method static warn(string ...$message) Print warn style message line.
 * @method static info(string ...$message) Print info style message line.
 * @method static note(string ...$message) Print note style message line.
 * @method static notice(string ...$message) Print notice style message line.
 * @method static success(string ...$message) Print success style message line.
 */
class Cli
{
    use ReadMessageTrait, WriteMessageTrait;

    /**
     * @param string $method {@see Color::STYLES}
     * @param array  $args
     */
    public static function __callStatic(string $method, array $args)
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

        throw new RuntimeException('call unknown method: ' . $method);
    }

    /**
     * Print colored message to STDOUT
     *
     * @param string|array      $message
     * @param string|array|null $style
     */
    public static function colored($message, $style = 'info'): void
    {
        $str = is_array($message) ? implode(' ', $message) : (string)$message;

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
     * @param string                $text
     * @param string|int|array|null $style
     *
     * @return string
     */
    public static function color(string $text, $style = null): string
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
     * @param string $msg
     * @param array  $data
     * @param string $type
     * @param array  $opts
     *  [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     *  ]
     */
    public static function clog(string $msg, array $data = [], string $type = 'info', array $opts = []): void
    {
        if (isset(self::LOG_LEVEL2TAG[$type])) {
            $type = ColorTag::add(strtoupper($type), self::LOG_LEVEL2TAG[$type]);
        }

        $userOpts = [];

        foreach ($opts as $n => $v) {
            if (is_numeric($n) || strpos($n, '_') === 0) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString  = $userOpts ? ' ' . implode(' ', $userOpts) : '';
        $dataString = $data ? PHP_EOL . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '';

        self::writef("%s [%s]%s %s %s\n", date('Y/m/d H:i:s'), $type, $optString, trim($msg), $dataString);
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
     *
     * @param resource|mixed $fileDescriptor
     *
     * @return boolean
     */
    public static function isInteractive($fileDescriptor): bool
    {
        // PHP 7 >= 7.2.0
        if (function_exists('stream_isatty')) {
            return \stream_isatty($fileDescriptor);
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
