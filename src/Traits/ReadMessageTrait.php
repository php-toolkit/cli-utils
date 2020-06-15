<?php declare(strict_types=1);

namespace Toolkit\Cli\Traits;

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
            self::write($message, $nl);
        }

        $opts = array_merge([
            'length' => 1024,
            'stream' => self::$inputStream,
        ], $opts);

        return file_get_contents($opts['stream'], $opts['length']);
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
        if ($message) {
            self::write($message, $nl);
        }

        $opts = array_merge([
            'length' => 1024,
            'stream' => self::$inputStream,
        ], $opts);

        return trim(fgets($opts['stream'], $opts['length']));
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
            self::write($message, $nl);
        }

        $opts = array_merge([
            'length'    => 1024,
            'stream'    => self::$inputStream,
            'allowTags' => null,
        ], $opts);

        return trim(fgetss($opts['stream'], $opts['length'], $opts['allowTags']));
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
