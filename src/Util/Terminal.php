<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use BadMethodCallException;
use Toolkit\Cli\Cli;
use function array_keys;
use function exec;
use function strpos;

/**
 * Class Terminal - terminal control by ansiCode
 *
 * @package Toolkit\Cli
 *
 * @method static showCursor()
 * @method static hideCursor()
 * @method static savePosition()
 * @method static restorePosition()
 * @method static toTop()
 * @method static toColumn(int $step)
 * @method static up(int $step = 1)
 * @method static down(int $step = 1)
 * @method static forward(int $step = 1)
 * @method static backward(int $step = 1) Moves the terminal cursor backward
 * @method static toPrevNLineStart(int $step = 1)
 * @method static toNextNLineStart(int $step = 1)
 * @method static coordinate(int $col, int $row = 0)
 * @method static clearScreen()
 * @method static clearLine()
 * @method static clearToScreenBegin()
 * @method static clearToScreenEnd()
 * @method static scrollUp(int $step = 1)
 * @method static scrollDown(int $step = 1)
 * @method static showSecondaryScreen()
 * @method static showPrimaryScreen()
 */
final class Terminal
{
    /*
     * 2K 清除本行
     * \x0D = \r = 13 回车，回到行首
     * ESC = \x1B = \033 = 27
     *
     * https://www.sitepoint.com/howd-they-do-it-phpsnake-detecting-keypresses/
     */

    public const BEGIN_CHAR = "\033[";

    public const END_CHAR = "\033[0m";

    public const END_CODE = '0';

    // Control cursor code name list.
    // more @see [[self::CURSOR_CONTROL_CODES]]

    public const CURSOR_HIDE = 'hideCursor';

    public const CURSOR_SHOW = 'showCursor';

    public const SAVE_POSITION = 'savePosition';

    public const RESTORE_POSITION = 'restorePosition';

    public const CURSOR_UP = 'up';

    public const CURSOR_DOWN = 'down';

    public const CURSOR_FORWARD = 'forward';

    public const CURSOR_BACKWARD = 'backward';

    public const CURSOR_TO_TOP = 'toTop'; // move cursor to top

    public const CURSOR_TO_COLUMN = 'toColumn';

    public const TO_PREV_N_LINE_BEGIN = 'toPrevNLineBegin';

    public const TO_NEXT_N_LINE_BEGIN = 'toNextNLineBegin';

    public const CURSOR_COORDINATE = 'coordinate';

    /**
     * Control cursor code list
     *
     * @var array
     */
    public const CURSOR_CONTROL_CODES = [
        // Hides the cursor. Use [show] to bring it back.
        // - 隐藏光标。使用 [show] 将其带回来。
        self::CURSOR_HIDE          => '?25l',

        // Will show a cursor again when it has been hidden by [hide]
        // - 当光标被 [hide] 隐藏时会再次显示
        self::CURSOR_SHOW          => '?25h',

        // Saves the current cursor position, Position can then be restored with [restorePosition].
        // - 保存当前光标位置，然后可以使用[restorePosition]恢复位置
        self::SAVE_POSITION        => 's',

        // Restores the cursor position saved with [savePosition] - 恢复[savePosition]保存的光标位置
        self::RESTORE_POSITION     => 'u',

        // Moves the terminal cursor to top - 移动终端光标到顶部
        self::CURSOR_TO_TOP        => 'H',

        // Moves the terminal cursor up - 向上移动终端光标
        self::CURSOR_UP            => '%dA',

        // Moves the terminal cursor down - 向下移动终端光标
        self::CURSOR_DOWN          => '%dB',

        // Moves the terminal cursor forward - 移动终端光标前进多远
        self::CURSOR_FORWARD       => '%dC',

        // Moves the terminal cursor backward - 移动终端光标后退多远
        self::CURSOR_BACKWARD      => '%dD',

        // Moves the terminal cursor to the beginning of the previous line - 移动终端光标到前N行的开始
        self::TO_PREV_N_LINE_BEGIN => '%dF',

        // Moves the terminal cursor to the beginning of the next line - 移动终端光标到下N行的开始
        self::TO_NEXT_N_LINE_BEGIN => '%dE',

        // Moves the cursor to given column - 本行移动光标到指定列
        self::CURSOR_TO_COLUMN     => '%d;0H',

        // Moves the cursor to an absolute position given as column and row
        // > 将光标移动到以列和行给出的绝对位置
        //  - $column 1-based column number, 1 is the left edge of the screen.
        //  - $row    1-based row number, 1 is the top edge of the screen. if not set, will move cursor only in current line.
        // only column: '%dG' == '%d;0H', column and row: '%d;%dH'.
        self::CURSOR_COORDINATE    => '%dG|%d;%dH'
    ];

    // Control screen code name list.
    // more @see [[self::SCREEN_CONTROL_CODES]]

    public const CLEAR = 'clearScreen'; // alias of CLEAR_SCREEN

    public const CLEAR_SCREEN = 'clearScreen';

    public const CLEAR_TO_SCREEN_BEGIN = 'clearToScreenBegin';

    public const CLEAR_LINE = 'clearLine';

    public const CLEAR_TO_LINE_BEGIN = 'clearToLineBegin';

    public const CLEAR_TO_LINE_END = 'clearToLineEnd';

    public const SCROLL_UP = 'scrollUp';

    public const SCROLL_DOWN = 'scrollDown';

    public const SHOW_SECONDARY_SCREEN = 'showSecondaryScreen';

    public const SHOW_PRIMARY_SCREEN = 'showPrimaryScreen';

    /**
     * Control screen code list
     *
     * @var array
     */
    public const SCREEN_CONTROL_CODES = [
        // Clears entire screen content - 清除整个屏幕内容
        self::CLEAR_SCREEN          => '2J', // "\033[2J"

        // Clears text from cursor to the beginning of the screen
        // > 从光标清除文本到屏幕的开头
        self::CLEAR_TO_SCREEN_BEGIN => '1J',

        // Clears the line - 清除此行
        self::CLEAR_LINE            => '2K',

        // Clears text from cursor position to the beginning of the line
        // > 清除此行从光标位置开始到开始的字符
        self::CLEAR_TO_LINE_BEGIN   => '1K',

        // Clears text from cursor position to the end of the line
        // > 清除此行从光标位置开始到结束的字符
        self::CLEAR_TO_LINE_END     => '0K',

        // Scrolls whole page up. e.g "\033[2S" scroll up 2 line. - 上移多少行
        self::SCROLL_UP             => '%dS',

        // Scrolls whole page down.e.g "\033[2T" scroll down 2 line. - 下移多少行
        self::SCROLL_DOWN           => '%dT',

        // show the secondary screen 显示辅助屏幕
        self::SHOW_SECONDARY_SCREEN => '?47h',

        // show the primary screen 显示主屏幕
        self::SHOW_PRIMARY_SCREEN   => '?47l',
    ];

    /**
     * current class's instance
     *
     * @var self
     */
    private static $instance;

    /**
     * @var bool
     */
    private $echoBack = true;

    /**
     * @return Terminal
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * @param string $method
     * @param array  $args
     */
    public static function __callStatic(string $method, array $args = [])
    {
        if (isset(self::CURSOR_CONTROL_CODES[$method])) {
            self::instance()->cursor($method, ...$args);
        } elseif (isset(self::SCREEN_CONTROL_CODES[$method])) {
            self::instance()->screen($method, ...$args);
        } else {
            throw new BadMethodCallException('call not exists method: ' . $method);
        }
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

        return self::BEGIN_CHAR . implode(';', (array)$format) . $type;
    }

    /**
     * Disables echoing every character back to the terminal. This means
     * we do not have to clear the line when reading.
     */
    public function disableEchoBack(): void
    {
        exec('stty -echo');
        $this->echoBack = false;
    }

    /**
     * Enable echoing back every character input to the terminal.
     */
    public function enableEchoBack(): void
    {
        exec('stty echo');
        $this->echoBack = true;
    }

    /**
     * @return bool
     */
    public function isEchoBack(): bool
    {
        return $this->echoBack;
    }

    /**
     * control cursor move
     *
     * @param string   $typeName
     * @param int      $arg1
     * @param int|null $arg2
     *
     * @return $this
     */
    public function cursor(string $typeName, int $arg1 = 1, int $arg2 = null): self
    {
        if (!isset(self::CURSOR_CONTROL_CODES[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::CURSOR_CONTROL_CODES[$typeName];

        // allow argument
        if (false !== strpos($code, '%')) {
            // The special code: ` 'coordinate' => '%dG|%d;%dH' `
            if ($typeName === self::CURSOR_COORDINATE) {
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
    public function screen(string $typeName, int $step = 1): self
    {
        if (!isset(self::SCREEN_CONTROL_CODES[$typeName])) {
            Cli::stderr("The [$typeName] is not supported cursor control.");
        }

        $code = self::SCREEN_CONTROL_CODES[$typeName];

        // allow argument
        if (false !== strpos($code, '%')) {
            $code = sprintf($code, $step);
        }

        echo self::build($code, '');
        return $this;
    }

    public function begin(): void
    {
        echo self::BEGIN_CHAR;
    }

    public function start(): void
    {
        echo self::BEGIN_CHAR;
    }

    public function reset(): void
    {
        echo self::END_CHAR;
    }

    /**
     * @param string $ctrlName
     *
     * @return bool
     */
    public static function isSupported(string $ctrlName): bool
    {
        return self::isSupportedCursor($ctrlName) || self::isSupportedScreen($ctrlName);
    }

    /**
     * @param string $ctrlName
     *
     * @return bool
     */
    public static function isSupportedCursor(string $ctrlName): bool
    {
        return isset(self::CURSOR_CONTROL_CODES[$ctrlName]);
    }

    /**
     * @param string $ctrlName
     *
     * @return bool
     */
    public static function isSupportedScreen(string $ctrlName): bool
    {
        return isset(self::SCREEN_CONTROL_CODES[$ctrlName]);
    }

    /**
     * @return array
     */
    public static function getCursorControlNames(): array
    {
        return array_keys(self::CURSOR_CONTROL_CODES);
    }

    /**
     * @return array
     */
    public static function getScreenControlNames(): array
    {
        return array_keys(self::SCREEN_CONTROL_CODES);
    }
}
