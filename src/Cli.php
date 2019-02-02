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

        return \trim(\fgets(\STDIN));
    }

    /**
     * write message to console
     * @param      $messages
     * @param bool $nl
     * @param bool $quit
     */
    public static function write($messages, $nl = true, $quit = false): void
    {
        if (\is_array($messages)) {
            $messages = implode($nl ? \PHP_EOL : '', $messages);
        }

        self::stdout(Color::parseTag($messages), $nl, $quit);
    }

    /**
     * Logs data to stdout
     * @param string   $message
     * @param bool     $nl
     * @param bool|int $quit
     */
    public static function stdout(string $message, $nl = true, $quit = false): void
    {
        fwrite(\STDOUT, $message . ($nl ? \PHP_EOL : ''));
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
    public static function stderr(string $message, $nl = true, $quit = -1): void
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
        if (\DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === \PHP_WINDOWS_VERSION_MAJOR . '.' . \PHP_WINDOWS_VERSION_MINOR . '.' . \PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!\defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(\STDOUT);
    }

    /**
     * @return bool
     */
    public static function isSupport256Color(): bool
    {
        return \DIRECTORY_SEPARATOR === '/' && strpos(getenv('TERM'), '256color') !== false;
    }

    /**
     * @return bool
     */
    public static function isAnsiSupport(): bool
    {
        if (\DIRECTORY_SEPARATOR === '\\') {
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

    /**
     * clear Ansi Code
     * @param string $string
     * @return string
     */
    public static function stripAnsiCode(string $string): string
    {
        return \preg_replace('/\033\[[\d;?]*\w/', '', $string);
    }
}
