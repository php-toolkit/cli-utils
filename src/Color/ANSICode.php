<?php declare(strict_types=1);

namespace Toolkit\Cli\Color;

/**
 * Class ANSICode
 * @package Toolkit\Cli\Util
 */
abstract class ANSICode
{
    public const RESET = 0;

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
    public const FG_BLACK = 30;

    public const FG_RED = 31;

    public const FG_GREEN = 32;

    public const FG_BROWN = 33; // like yellow

    public const FG_BLUE = 34;

    public const FG_CYAN = 36;

    public const FG_WHITE = 37;

    public const FG_DEFAULT = 39;

    // extra Foreground color
    public const FG_DARK_GRAY = 90;

    public const FG_LIGHT_RED = 91;

    public const FG_LIGHT_GREEN = 92;

    public const FG_LIGHT_YELLOW = 93;

    public const FG_LIGHT_BLUE = 94;

    public const FG_LIGHT_MAGENTA = 95;

    public const FG_LIGHT_CYAN = 96;

    public const FG_LIGHT_WHITE = 97;

    // Background color
    public const BG_BLACK = 40;

    public const BG_RED = 41;

    public const BG_GREEN = 42;

    public const BG_BROWN = 43; // like yellow

    public const BG_BLUE = 44;

    public const BG_CYAN = 46;

    public const BG_WHITE = 47;

    public const BG_DEFAULT = 49;

    // extra Background color
    public const BG_DARK_GRAY = 100;

    public const BG_LIGHT_RED = 101;

    public const BG_LIGHT_GREEN = 102;

    public const BG_LIGHT_YELLOW = 103;

    public const BG_LIGHT_BLUE = 104;

    public const BG_LIGHT_MAGENTA = 105;

    public const BG_LIGHT_CYAN = 106;

    public const BG_WHITE_W = 107;

    // color option
    public const BOLD = 1;      // 加粗

    public const FUZZY = 2;      // 模糊(不是所有的终端仿真器都支持)

    public const ITALIC = 3;      // 斜体(不是所有的终端仿真器都支持)

    public const UNDERSCORE = 4;      // 下划线

    public const BLINK = 5;      // 闪烁

    public const REVERSE = 7;      // 颠倒的 交换背景色与前景色

    public const CONCEALED = 8;      // 隐匿的

}