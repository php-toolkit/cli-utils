<?php declare(strict_types=1);

namespace Toolkit\Cli\Util;

use function array_shift;
use function explode;
use function file_get_contents;
use function function_exists;
use function readline;
use function readline_add_history;
use function readline_callback_handler_install;
use function readline_callback_handler_remove;
use function readline_callback_read_char;
use function readline_completion_function;
use function readline_info;
use function readline_list_history;
use function readline_on_new_line;
use function readline_read_history;
use function readline_redisplay;
use function readline_write_history;
use function sys_get_temp_dir;
use function trim;
use const PHP_EOL;

/**
 * Class Readline
 *
 * @package Toolkit\Cli\Util
 */
class Readline
{
    /**
     * @return bool
     */
    public static function isSupported(): bool
    {
        return function_exists('readline');
    }

    /**
     * @param string $prompt
     *
     * @return string
     */
    public static function readline(string $prompt = ''): string
    {
        return trim((string)readline($prompt));
    }

    /**
     * Redraws the display
     */
    public static function redisplay(): void
    {
        readline_redisplay();
    }

    /**
     * Tell readline to move the cursor to a new line
     */
    public static function moveToNewline(): void
    {
        readline_on_new_line();
    }

    /**
     * Registers a completion function
     *
     * ```php
     * $func = function ($str, $index) {
     *  $matches = [];
     *  // match some by $str
     *  return $matches;
     * }
     *
     * Readline::registerCompleter($func);
     * ```
     *
     * @param callable $callback
     *
     * @return bool
     */
    public static function registerCompleter(callable $callback): bool
    {
        return readline_completion_function($callback);
    }

    /**
     * @param string   $prompt
     * @param callable $callback
     *
     * @return bool
     */
    public static function installHandler(string $prompt, callable $callback): bool
    {
        return readline_callback_handler_install($prompt, $callback);
    }

    /**
     * Removes a previously installed callback handler and restores terminal settings
     *
     * @return bool
     */
    public static function removeHandler(): bool
    {
        return readline_callback_handler_remove();
    }

    /**
     * Reads a character and informs the readline callback interface when a line is received
     */
    public static function callbackReadChar(): void
    {
        // 当一个行被接收时读取一个字符并且通知 readline 调用回调函数
        readline_callback_read_char();
    }

    /**
     * Gets/sets various internal readline variables
     *
     * ```php
     * [
     *  line_buffer => string, // the entire contents of the line buffer
     *  point => int, // the current position of the cursor in the buffer
     *  end => int, // the position of the last character in the buffer
     * ]
     * ```
     *
     * @return array
     */
    public static function getInfo(): array
    {
        return readline_info();
    }

    /**
     * Gets/sets various internal readline variables
     */
    public static function getVar(string $name)
    {
        return readline_info($name);
    }

    /**
     * Sets various internal readline variables
     */
    public static function setVar(string $name, $value)
    {
        return readline_info($name, $value);
    }

    /**
     * @var string
     */
    private static $listTmpFile = '';

    /**
     * @return bool
     */
    public static function hasListHistoryFunc(): bool
    {
        // if `READLINE_LIB=libedit` not support the function.
        return function_exists('readline_list_history');
    }

    /**
     * Lists the history in the memory
     *
     * @return array
     */
    public static function listHistory(): array
    {
        if (self::hasListHistoryFunc()) {
            return readline_list_history();
        }

        // - if not support list func, dump history to tmp file then read contents.

        if (!self::$listTmpFile) {
            self::$listTmpFile = tempnam(sys_get_temp_dir(), 'rl_his_');
        }

        readline_write_history(self::$listTmpFile);

        $list = [];
        $text = file_get_contents(self::$listTmpFile);
        if ($text) {
            $list = explode(PHP_EOL, trim($text));
            // first line is mark. eg: '_HiStOrY_V2_'
            array_shift($list);
        }

        return $list;
    }

    /**
     * @param string $input
     *
     * @return bool
     */
    public static function addHistory(string $input): bool
    {
        return readline_add_history($input);
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    public static function loadHistory(string $filepath): bool
    {
        if ($filepath) {
            return readline_read_history($filepath);
        }

        return false;
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    public static function dumpHistory(string $filepath): bool
    {
        if ($filepath) {
            return readline_write_history($filepath);
        }

        return false;
    }

    /**
     * @return string
     */
    public static function getListTmpFile(): string
    {
        return self::$listTmpFile;
    }

    /**
     * @param string $listTmpFile
     */
    public static function setListTmpFile(string $listTmpFile): void
    {
        self::$listTmpFile = $listTmpFile;
    }
}
