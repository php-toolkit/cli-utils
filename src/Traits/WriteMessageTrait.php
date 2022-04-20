<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Traits;

use Stringable;
use Toolkit\Cli\Style;
use function count;
use function fopen;
use function implode;
use function is_string;
use const PHP_EOL;
use const STDERR;
use const STDOUT;

/**
 * Trait WriteMessageTrait
 *
 * @package Toolkit\Cli\Traits
 */
trait WriteMessageTrait
{
    /** @var string */
    private static string $buffer = '';

    /** @var bool */
    private static bool $buffering = false;

    /** @var resource */
    private static $outputStream = STDOUT;

    /** @var resource */
    private static $errorStream = STDERR;

    /***********************************************************************************
     * Output message
     ***********************************************************************************/

    /**
     * @param string|int ...$args
     */
    public function echo(...$args): void
    {
        echo count($args) > 1 ? implode(' ', $args) : $args[0];
    }

    /**
     * @param string|int ...$args
     */
    public function echoln(...$args): void
    {
        echo(count($args) > 1 ? implode(' ', $args) : $args[0]), PHP_EOL;
    }

    /**
     * Format and write message to terminal. like printf()
     *
     * @param string $format
     * @param mixed  ...$args
     *
     * @return int
     */
    public static function writef(string $format, ...$args): int
    {
        return self::write(sprintf($format, ...$args), false);
    }

    /**
     * Format and write message to terminal. like printf()
     *
     * @param string $format
     * @param mixed  ...$args
     *
     * @return int
     */
    public static function printf(string $format, ...$args): int
    {
        return self::write(sprintf($format, ...$args), false);
    }

    /**
     * Write raw data to stdout, will disable color render.
     *
     * @param array|string $message
     * @param bool $nl
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts
     *
     * @return int
     */
    public static function writeRaw(array|string $message, bool $nl = true, bool $quit = false, array $opts = []): int
    {
        $opts['color'] = false;
        return self::write($message, $nl, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     *
     * @param array|string $message
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts
     *
     * @return int
     */
    public static function writeln(array|string $message, bool $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * Write data to stdout with newline.
     *
     * @param array|string $message
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int}  $opts Some options for write
     *
     * @return int
     */
    public static function println(array|string $message, bool $quit = false, array $opts = []): int
    {
        return self::write($message, true, $quit, $opts);
    }

    /**
     * Write message to stdout.
     *
     * @param array|string $message
     * @param bool $quit
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts Some options for write
     *
     * @return int
     */
    public static function print(array|string $message, bool $quit = false, array $opts = []): int
    {
        return self::write($message, false, $quit, $opts);
    }

    /**
     * Write a message to standard output stream.
     *
     * For $opts:
     *
     * ```php
     * [
     *   'color'  => bool, // whether render color, default is: True.
     *   'stream' => resource, // the stream resource, default is: STDOUT
     *   'flush'  => bool, // flush the stream data, default is: True
     *   'quit'   => bool, // quit after write.
     *   'quitCode' => int, // quit code.
     *  ]
     * ```
     *
     * @param array|string|Stringable $messages Output message
     * @param bool $nl  True - Will add line breaks, False Raw output.
     * @param bool $quit whether quit after write
     * @param array{color:bool,stream:resource,flush:bool,quit:bool,quitCode:int} $opts Some options for write
     *
     * @return int
     */
    public static function write(array|string|Stringable $messages, bool $nl = true, bool $quit = false, array $opts = []): int
    {
        if (is_array($messages)) {
            $messages = implode($nl ? PHP_EOL : '', $messages);
        }

        $messages = (string)$messages;

        if (!isset($opts['color']) || $opts['color']) {
            $messages = Style::global()->render($messages);
        } else {
            $messages = Style::stripColor($messages);
        }

        // if open buffering
        if (self::isBuffering()) {
            self::$buffer .= $messages . ($nl ? PHP_EOL : '');
            if (!$quit) {
                return 0;
            }

            $messages = self::$buffer;
            // clear buffer
            self::$buffer = '';
        } else {
            $messages .= $nl ? PHP_EOL : '';
        }

        $stream = self::$outputStream;
        if (!empty($opts['stream'])) {
            $stream = is_string($opts['stream']) ? fopen($opts['stream'], 'wb+') : $opts['stream'];
        }

        fwrite($stream, $messages);
        if (!isset($opts['flush']) || $opts['flush']) {
            fflush($stream);
        }

        // if want quit.
        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * Logs data to stdout
     *
     * @param array|string $text
     * @param bool $nl
     * @param bool $quit
     */
    public static function stdout(array|string $text, bool $nl = true, bool $quit = false): void
    {
        self::write($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     *
     * @param array|string $text
     * @param bool $nl
     * @param bool $quit
     * @param int $quitCode
     */
    public static function stderr(array|string $text, bool $nl = true, bool $quit = false, int $quitCode = -2): void
    {
        self::write($text, $nl, $quit, [
            'stream'   => self::$errorStream,
            'quitCode' => $quitCode,
        ]);
    }

    /***********************************************************************************
     * Output buffer
     ***********************************************************************************/

    /**
     * @return bool
     */
    public static function isBuffering(): bool
    {
        return self::$buffering;
    }

    /**
     * @return string
     */
    public static function getBuffer(): string
    {
        return self::$buffer;
    }

    /**
     * @param string $buffer
     */
    public static function setBuffer(string $buffer): void
    {
        self::$buffer = $buffer;
    }

    /**
     * Start buffering
     */
    public static function startBuffer(): void
    {
        self::$buffering = true;
    }

    /**
     * Clear buffering
     */
    public static function clearBuffer(): void
    {
        self::$buffer = '';
    }

    /**
     * Stop buffering
     *
     * @param bool  $flush Whether flush buffer to output stream
     * @param bool  $nl    Default is False, because the last write() have been added "\n"
     * @param bool  $quit
     * @param array $opts
     *
     * @return string If flush = False, will return all buffer text.
     * @see write()
     */
    public static function stopBuffer(
        bool $flush = true,
        bool $nl = false,
        bool $quit = false,
        array $opts = []
    ): string {
        self::$buffering = false;

        if ($flush && self::$buffer) {
            // all text have been rendered by Style::render() in every write();
            $opts['color'] = false;

            // flush to stream
            self::write(self::$buffer, $nl, $quit, $opts);

            // clear buffer
            self::$buffer = '';
        }

        return self::$buffer;
    }

    /**
     * Stop buffering and flush buffer text
     *
     * @param bool  $nl
     * @param bool  $quit
     * @param array $opts
     *
     * @see write()
     */
    public static function flushBuffer(bool $nl = false, bool $quit = false, array $opts = []): void
    {
        self::stopBuffer(true, $nl, $quit, $opts);
    }

    /**
     * @return resource
     */
    public static function getOutputStream()
    {
        return self::$outputStream;
    }

    /**
     * @param resource $outputStream
     */
    public static function setOutputStream($outputStream): void
    {
        self::$outputStream = $outputStream;
    }

    /**
     * @return resource
     */
    public static function getErrorStream()
    {
        return self::$errorStream;
    }

    /**
     * @param resource $errorStream
     */
    public static function setErrorStream($errorStream): void
    {
        self::$errorStream = $errorStream;
    }
}
