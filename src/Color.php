<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use function array_filter;
use function array_keys;
use function implode;
use function is_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function strip_tags;
use function strpos;

/**
 * Class Color
 *
 * @package Toolkit\Cli
 * // basic
 * @method string red(string $text)
 * @method string blue(string $text)
 * @method string cyan(string $text)
 * @method string black(string $text)
 * @method string brown(string $text)
 * @method string green(string $text)
 * @method string white(string $text)
 * @method string yellow(string $text)
 * @method string magenta(string $text)
 *
 * // alert
 * @method string info(string $text)
 * @method string danger(string $text)
 * @method string error(string $text)
 * @method string notice(string $text)
 * @method string warning(string $text)
 * @method string success(string $text)
 *
 * // more please @see Color::STYLES
 */
class Color
{
    public const RESET  = 0;

    public const NORMAL = 0;

    /** Foreground base value */
    public const FG_BASE = 30;

    /** Background base value */
    public const BG_BASE = 40;

    /** Extra Foreground base value */
    public const FG_EXTRA = 90;

    /** Extra Background base value */
    public const BG_EXTRA = 100;

    // Foreground color
    public const FG_BLACK   = 30;

    public const FG_RED     = 31;

    public const FG_GREEN   = 32;

    public const FG_BROWN   = 33; // like yellow

    public const FG_BLUE    = 34;

    public const FG_CYAN    = 36;

    public const FG_WHITE   = 37;

    public const FG_DEFAULT = 39;

    // extra Foreground color
    public const FG_DARK_GRAY     = 90;

    public const FG_LIGHT_RED     = 91;

    public const FG_LIGHT_GREEN   = 92;

    public const FG_LIGHT_YELLOW  = 93;

    public const FG_LIGHT_BLUE    = 94;

    public const FG_LIGHT_MAGENTA = 95;

    public const FG_LIGHT_CYAN    = 96;

    public const FG_LIGHT_WHITE   = 97;

    // Background color
    public const BG_BLACK   = 40;

    public const BG_RED     = 41;

    public const BG_GREEN   = 42;

    public const BG_BROWN   = 43; // like yellow

    public const BG_BLUE    = 44;

    public const BG_CYAN    = 46;

    public const BG_WHITE   = 47;

    public const BG_DEFAULT = 49;

    // extra Background color
    public const BG_DARK_GRAY     = 100;

    public const BG_LIGHT_RED     = 101;

    public const BG_LIGHT_GREEN   = 102;

    public const BG_LIGHT_YELLOW  = 103;

    public const BG_LIGHT_BLUE    = 104;

    public const BG_LIGHT_MAGENTA = 105;

    public const BG_LIGHT_CYAN    = 106;

    public const BG_WHITE_W       = 107;

    // color option
    public const BOLD       = 1;      // 加粗

    public const FUZZY      = 2;      // 模糊(不是所有的终端仿真器都支持)

    public const ITALIC     = 3;      // 斜体(不是所有的终端仿真器都支持)

    public const UNDERSCORE = 4;      // 下划线

    public const BLINK      = 5;      // 闪烁

    public const REVERSE    = 7;      // 颠倒的 交换背景色与前景色

    public const CONCEALED  = 8;      // 隐匿的

    /**
     * There are some internal styles
     * custom style: fg;bg;opt
     *
     * @var array
     */
    public const STYLES = [
        // basic
        'normal'      => '39',// no color
        'red'         => '0;31',
        'blue'        => '0;34',
        'cyan'        => '0;36',
        'black'       => '0;30',
        'green'       => '0;32',
        'brown'       => '0;33',
        'white'       => '1;37',
        'ylw0'        => '0;33',
        'yellow0'     => '0;33',
        'ylw'         => '1;33',
        'yellow'      => '1;33',
        'mga0'        => '0;35',
        'magenta0'    => '0;35',
        'mga'         => '1;35',
        'magenta'     => '1;35',

        // alert
        'suc'         => '1;32',// same 'green' and 'bold'
        'success'     => '1;32',
        'info'        => '0;32',// same 'green'
        'comment'     => '0;33',// same 'brown'
        'note'        => '36;1',
        'notice'      => '36;4',
        'warn'        => '0;30;43',
        'warning'     => '0;30;43',
        'danger'      => '0;31',// same 'red'
        'err'         => '97;41',
        'error'       => '97;41',

        // more
        'lightRed'    => '1;31',
        'light_red'   => '1;31',
        'lightGreen'  => '1;32',
        'light_green' => '1;32',
        'lightBlue'   => '1;34',
        'light_blue'  => '1;34',
        'lightCyan'   => '1;36',
        'light_cyan'  => '1;36',
        'lightDray'   => '37',
        'light_gray'  => '37',

        'darkDray'       => '90',
        'dark_gray'      => '90',
        'lightYellow'    => '93',
        'light_yellow'   => '93',
        'lightMagenta'   => '95',
        'light_magenta'  => '95',

        // extra
        'lightRedEx'     => '91',
        'light_red_ex'   => '91',
        'lightGreenEx'   => '92',
        'light_green_ex' => '92',
        'lightBlueEx'    => '94',
        'light_blue_ex'  => '94',
        'lightCyanEx'    => '96',
        'light_cyan_ex'  => '96',
        'whiteEx'        => '97',
        'white_ex'       => '97',

        // option
        'b'              => '0;1',
        'bold'           => '0;1',
        'fuzzy'          => '2',
        'i'              => '0;3',
        'italic'         => '0;3',
        'underscore'     => '4',
        'blink'          => '5',
        'reverse'        => '7',
        'concealed'      => '8',
    ];

    // Regex to match color tags
    public const COLOR_TAG = '/<([a-z=;]+)>(.*?)<\/\\1>/s';

    // CLI color template
    public const COLOR_TPL = "\033[%sm%s\033[0m";

    /**
     * Flag to remove color codes from the output
     *
     * @var bool
     */
    private static $noColor = false;

    /**
     * Force render color code
     *
     * @var bool
     */
    private static $forceColor = false;

    /**
     * @param string $method
     * @param array  $args
     *
     * @return string
     */
    public static function __callStatic(string $method, array $args)
    {
        if (isset(self::STYLES[$method])) {
            return self::render($args[0], $method);
        }

        return '';
    }

    /**
     * Apply style for text
     *
     * @param string $style
     * @param string $text
     *
     * @return string
     */
    public static function apply(string $style, string $text): string
    {
        return self::render($text, $style);
    }

    /**
     * Format and print to STDOUT
     *
     * @param string $format
     * @param mixed  ...$args
     */
    public static function printf(string $format, ...$args): void
    {
        echo self::render(sprintf($format, ...$args));
    }

    /**
     * Print colored message to STDOUT
     *
     * @param string|array $messages
     * @param string       $style
     */
    public static function println($messages, string $style = 'info'): void
    {
        $string = is_array($messages) ? implode("\n", $messages) : (string)$messages;

        echo self::render($string . "\n", $style);
    }

    /**
     * @param string $text
     * @param string $tag
     *
     * @return string
     */
    public static function addTag(string $text, string $tag = 'info'): string
    {
        return ColorTag::add($text, $tag);
    }

    /*******************************************************************************
     * color render
     ******************************************************************************/

    /**
     * Render text, apply color code
     *
     * @param string       $text
     * @param string|array $style
     * - string: 'green', 'blue'
     * - array: [Color::FG_GREEN, Color::BG_WHITE, Color::UNDERSCORE]
     *
     * @return string
     */
    public static function render(string $text, $style = null): string
    {
        if (!$text) {
            return $text;
        }

        // shouldn't render color, clear color code.
        if (!self::isShouldRenderColor()) {
            return self::clearColor($text);
        }

        $color = '';

        // use defined style: 'green'
        if (is_string($style)) {
            $color = self::STYLES[$style] ?? '';

        // custom style: [self::FG_GREEN, self::BG_WHITE, self::UNDERSCORE]
        } elseif (is_array($style)) {
            $color = implode(';', $style);

        // user color tag: <info>message</info>
        } elseif (strpos($text, '</') > 0) {
            return self::parseTag($text);
        }

        if (!$color) {
            return $text;
        }

        // $result = chr(27). "$color{$text}" . chr(27) . chr(27) . "[0m". chr(27);
        return sprintf(self::COLOR_TPL, $color, $text);
    }

    /**
     * parse color tag e.g: <info>message</info>
     *
     * @param string $text
     *
     * @return mixed|string
     */
    public static function parseTag(string $text)
    {
        return ColorTag::parse($text);
    }

    /**
     * @return bool
     */
    public static function isShouldRenderColor(): bool
    {
        // force render color code
        if (self::$forceColor) {
            return true;
        }

        // disable color
        if (self::$noColor) {
            return false;
        }

        // current env is support render color?
        return Cli::isSupportColor();
    }

    /**
     * Create a color style code from a parameter string.
     *
     * @param string $string e.g 'fg=white;bg=black;options=bold,underscore;extra=1'
     *
     * @return string
     */
    public static function stringToCode(string $string): string
    {
        return ColorCode::fromString($string)->toString();
    }

    /**
     * @param string $text
     * @param bool   $stripTag
     *
     * @return string
     */
    public static function clearColor(string $text, bool $stripTag = true): string
    {
        // return preg_replace('/\033\[(?:\d;?)+m/', '' , "\033[0;36mtext\033[0m");
        return preg_replace('/\033\[(?:\d;?)+m/', '', $stripTag ? strip_tags($text) : $text);
    }

    /**
     * @param string $style
     *
     * @return bool
     */
    public static function hasStyle(string $style): bool
    {
        return isset(self::STYLES[$style]);
    }

    /**
     * get all style names
     *
     * @return array
     */
    public static function getStyles(): array
    {
        return array_filter(array_keys(self::STYLES), static function ($style) {
            return !strpos($style, '_');
        });
    }

    /**
     * reset color config
     */
    public static function resetConfig(): void
    {
        self::$noColor = self::$forceColor = false;
    }

    /**
     * @return bool
     */
    public static function isNoColor(): bool
    {
        return self::$noColor;
    }

    /**
     * @param bool $noColor
     */
    public static function setNoColor(bool $noColor): void
    {
        self::$noColor = $noColor;
    }

    /**
     * @return bool
     */
    public static function isForceColor(): bool
    {
        return self::$forceColor;
    }

    /**
     * @param bool $forceColor
     */
    public static function setForceColor(bool $forceColor): void
    {
        self::$forceColor = $forceColor;
    }
}
