<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use Toolkit\Cli\Cli;
use function array_keys;
use function strpos;

/**
 * Class Terminal - terminal control by ansiCode
 *
 * @package Toolkit\Cli
 *
 * 2K 清除本行
 * \x0D = \r = 13 回车，回到行首
 * ESC = \x1B = \033 = 27
 */
final class Terminal
{
    public const BEGIN_CHAR = "\033[";

    public const END_CHAR = "\033[0m";

    // Control cursor code name list. more @see [[self::$ctrlCursorCodes]]
    public const CUR_HIDE = 'hide';

    public const CUR_SHOW = 'show';

    public const CUR_SAVE_POSITION = 'savePosition';

    public const CUR_RESTORE_POSITION = 'restorePosition';

    public const CUR_UP = 'up';

    public const CUR_DOWN = 'down';

    public const CUR_FORWARD = 'forward';

    public const CUR_BACKWARD = 'backward';

    public const CUR_NEXT_LINE = 'nextLine';

    public const CUR_PREV_LINE = 'prevLine';

    public const CUR_COORDINATE = 'coordinate';

    // Control screen code name list. more @see [[self::$ctrlScreenCodes]]
    public const CLEAR = 'clear';

    public const CLEAR_BEFORE_CURSOR = 'clearBeforeCursor';

    public const CLEAR_LINE = 'clearLine';

    public const CLEAR_LINE_BEFORE_CURSOR = 'clearLineBeforeCursor';

    public const CLEAR_LINE_AFTER_CURSOR = 'clearLineAfterCursor';

    public const SCROLL_UP = 'scrollUp';

    public const SCROLL_DOWN = 'scrollDown';

    /**
     * current class's instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Control cursor code list
     *
     * @var array
     */
    private static $ctrlCursorCodes = [
        // Hides the cursor. Use [show] to bring it back.
        'hide'            => '?25l',

        // Will show a cursor again when it has been hidden by [hide]
        'show'            => '?25h',

        // Saves the current cursor position, Position can then be restored with [restorePosition].
        // - 保存当前光标位置，然后可以使用[restorePosition]恢复位置
        'savePosition'    => 's',

        // Restores the cursor position saved with [savePosition] - 恢复[savePosition]保存的光标位置
        'restorePosition' => 'u',

        // Moves the terminal cursor up
        'up'              => '%dA',

        // Moves the terminal cursor down
        'down'            => '%B',

        // Moves the terminal cursor forward - 移动终端光标前进多远
        'forward'         => '%dC',

        // Moves the terminal cursor backward - 移动终端光标后退多远
        'backward'        => '%dD',

        // Moves the terminal cursor to the beginning of the previous line - 移动终端光标到前一行的开始
        'prevLine'        => '%dF',

        // Moves the terminal cursor to the beginning of the next line - 移动终端光标到下一行的开始
        'nextLine'        => '%dE',

        // Moves the cursor to an absolute position given as column and row
        // $column 1-based column number, 1 is the left edge of the screen.
        //  $row 1-based row number, 1 is the top edge of the screen. if not set, will move cursor only in current line.
        'coordinate'      => '%dG|%d;%dH' // only column: '%dG', column and row: '%d;%dH'.
    ];

    /**
     * Control screen code list
     *
     * @var array
     */
    private static $ctrlScreenCodes = [
        // Clears entire screen content - 清除整个屏幕内容
        'clear'                 => '2J', // "\033[2J"

        // Clears text from cursor to the beginning of the screen - 从光标清除文本到屏幕的开头
        'clearBeforeCursor'     => '1J',

        // Clears the line - 清除此行
        'clearLine'             => '2K',

        // Clears text from cursor position to the beginning of the line - 清除此行从光标位置开始到开始的字符
        'clearLineBeforeCursor' => '1K',

        // Clears text from cursor position to the end of the line - 清除此行从光标位置开始到结束的字符
        'clearLineAfterCursor'  => '0K',

        // Scrolls whole page up. e.g "\033[2S" scroll up 2 line. - 上移多少行
        'scrollUp'              => '%dS',

        // Scrolls whole page down.e.g "\033[2T" scroll down 2 line. - 下移多少行
        'scrollDown'            => '%dT',
    ];

    /**
     * @return Terminal
     */
    public static function instance(): Terminal
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * build ansi code string
     *
     * ```
     * Terminal::build(null, 'u');  // "\033[s" Saves the current cursor position
     * Terminal::build(0);          // "\033[0m" Build end char, Resets any ANSI format
     * ```
     *
     * @param mixed  $format
     * @param string $type
     *
     * @return string
     */
    public static function build($format, string $type = 'm'): string
    {
        $format = null === $format ? '' : implode(';', (array)$format);

        return "\033[" . implode(';', (array)$format) . $type;
    }

    /**
     * control cursor
     *
     * @param string   $typeName
     * @param int      $arg1
     * @param int|null $arg2
     *
     * @return $this
     */
    public function cursor(string $typeName, int $arg1 = 1, int $arg2 = null): self
    {
        if (!isset(self::$ctrlCursorCodes[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::$ctrlCursorCodes[$typeName];

        // allow argument
        if (false !== strpos($code, '%')) {
            // The special code: ` 'coordinate' => '%dG|%d;%dH' `
            if ($typeName === self::CUR_COORDINATE) {
                $codes = explode('|', $code);

                if (null === $arg2) {
                    $code = sprintf($codes[0], $arg1);
                } else {
                    $code = sprintf($codes[1], $arg1, $arg2);
                }
            } else {
                $code = sprintf($code, $arg1);
            }
        }

        echo self::build($code, '');

        return $this;
    }

    /**
     * control screen
     *
     * @param string   $typeName
     * @param int|null $step
     *
     * @return $this
     */
    public function screen(string $typeName, int $step = null): self
    {
        if (!isset(self::$ctrlScreenCodes[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::$ctrlScreenCodes[$typeName];

        // allow argument
        if (false !== strpos($code, '%')) {
            $code = sprintf($code, $step);
        }

        echo self::build($code, '');
        return $this;
    }

    public function reset(): void
    {
        echo self::END_CHAR;
    }

    /**
     * @return array
     */
    public static function supportedCursorCtrl(): array
    {
        return array_keys(self::$ctrlCursorCodes);
    }

    /**
     * @return array
     */
    public static function supportedScreenCtrl(): array
    {
        return array_keys(self::$ctrlScreenCodes);
    }
}
