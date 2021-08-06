<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Traits;

use Toolkit\Cli\Cli;
use Toolkit\Cli\Style;
use Toolkit\Cli\Util\Readline;
use function fopen;
use function implode;
use function is_array;
use function strip_tags;
use const PHP_EOL;
use const STDIN;

/**
 * Trait ReadMessageTrait
 *
 * @package Toolkit\Cli\Traits
 */
trait ReadMessageTrait
{
    /**
     * @var resource
     */
    private static $inputStream = STDIN;

    /**
     * Read message from STDIN
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     *
     * @return string
     */
    public static function read($message = null, bool $nl = false, array $opts = []): string
    {
        if ($message) {
            Cli::write($message, $nl);
        }

        $opts = array_merge([
            'length' => 1024,
            'stream' => self::$inputStream,
        ], $opts);

        return (string)file_get_contents($opts['stream'], $opts['length']);
    }

    /**
     * Gets line from file pointer
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     *   [
     *   'stream' => \STDIN
     *   ]
     *
     * @return string
     */
    public static function readln($message = null, bool $nl = false, array $opts = []): string
    {
        // TIP: use readline method, support left and right keypress.
        if (Readline::isSupported()) {
            if ($message && is_array($message)) {
                $message = implode($nl ? PHP_EOL : '', $message);
            }

            // $message = Color::render((string)$message);
            $message = Style::global()->render((string)$message);
            return Readline::readline($message);
        }

        // read from input stream
        if ($message) {
            Cli::write($message, $nl);
        }

        $opts = array_merge([
            'length' => 1024,
            'stream' => self::$inputStream,
        ], $opts);

        return trim((string)fgets($opts['stream'], $opts['length']));
    }

    /**
     * Read input information
     *
     * @param mixed $message 若不为空，则先输出文本
     * @param bool  $nl      true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public static function readRow($message = null, bool $nl = false): string
    {
        return self::readln($message, $nl);
    }

    /**
     * Gets line from file pointer and strip HTML tags
     *
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     *
     * @return string
     */
    public static function readSafe($message = null, bool $nl = false, array $opts = []): string
    {
        if ($message) {
            Cli::write($message, $nl);
        }

        $opts = array_merge([
            'length'    => 1024,
            'stream'    => self::$inputStream,
            'allowTags' => null,
        ], $opts);

        // up: fgetss has been DEPRECATED as of PHP 7.3.0
        // return trim(fgetss($opts['stream'], $opts['length'], $opts['allowTags']));
        return trim(strip_tags(fgets($opts['stream'], $opts['length']), $opts['allowTags']));
    }

    /**
     * Gets first character from file pointer
     *
     * @param string $message
     * @param bool   $nl
     *
     * @return string
     */
    public static function readChar(string $message = '', bool $nl = false): string
    {
        $line = self::readln($message, $nl);

        return $line !== '' ? $line[0] : '';
    }

    /**
     * Read input first char
     *
     * @param string $message
     * @param bool   $nl
     *
     * @return string
     */
    public static function readFirst(string $message = '', bool $nl = false): string
    {
        return self::readChar($message, $nl);
    }

    /**
     * Read password text.
     * NOTICE: only support linux
     *
     * @param string $prompt
     *
     * @return string
     * @link https://www.php.net/manual/zh/function.readline.php#120729
     */
    public static function readPassword(string $prompt = ''): string
    {
        $termDevice = '/dev/tty';
        if ($prompt) {
            Cli::write($prompt);
        }

        $h = fopen($termDevice, 'rb');
        if ($h === false) {
            // throw new RuntimeException("Failed to open terminal device");
            return ''; // probably not running in a terminal.
        }

        $line = trim((string)fgets($h));
        fclose($h);
        return $line;
    }

    /**
     * @return false|resource
     */
    public static function getInputStream()
    {
        return self::$inputStream;
    }

    /**
     * @param resource $inputStream
     */
    public static function setInputStream($inputStream): void
    {
        self::$inputStream = $inputStream;
    }

    /**
     */
    public static function resetInputStream(): void
    {
        self::$inputStream = STDIN;
    }
}
