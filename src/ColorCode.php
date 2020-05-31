<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use InvalidArgumentException;
use RuntimeException;
use function array_keys;
use function count;
use function explode;
use function implode;
use function str_replace;

/**
 * Class Color
 * - fg unset 39
 * - bg unset 49
 *
 * @package Inhere\Console\Component\Style
 */
class ColorCode
{
    /** Foreground base value */
    public const FG_BASE = 30;

    /** Background base value */
    public const BG_BASE = 40;

    /** Extra Foreground base value */
    public const FG_EXTRA = 90;

    /** Extra Background base value */
    public const BG_EXTRA = 100;

    // color
    public const BLACK = 'black';

    public const RED = 'red';

    public const GREEN = 'green';

    public const YELLOW = 'yellow'; // BROWN

    public const BLUE = 'blue';

    public const MAGENTA = 'magenta';

    public const CYAN = 'cyan';

    public const WHITE = 'white';

    public const NORMAL = 'normal';

    // color option
    public const BOLD = 'bold';       // 加粗

    public const FUZZY = 'fuzzy';      // 模糊(不是所有的终端仿真器都支持)

    public const ITALIC = 'italic';     // 斜体(不是所有的终端仿真器都支持)

    public const UNDERSCORE = 'underscore'; // 下划线

    public const BLINK = 'blink';      // 闪烁

    public const REVERSE = 'reverse';    // 颠倒的 交换背景色与前景色

    public const CONCEALED = 'concealed';  // 隐匿的

    /**
     * @var array Known color list
     */
    public const KNOWN_COLORS = [
        'black'   => 0,
        'red'     => 1,
        'green'   => 2,
        'yellow'  => 3,
        'blue'    => 4,
        'magenta' => 5, // 洋红色 洋红 品红色
        'cyan'    => 6, // 青色 青绿色 蓝绿色
        'white'   => 7,
        'normal'  => 9,
    ];

    /**
     * @var array Known option code
     */
    public const KNOWN_OPTIONS = [
        'bold'       => Color::BOLD,       // 加粗
        'fuzzy'      => Color::FUZZY,      // 模糊(不是所有的终端仿真器都支持)
        'italic'     => Color::ITALIC,     // 斜体(不是所有的终端仿真器都支持)
        'underscore' => Color::UNDERSCORE, // 下划线
        'blink'      => Color::BLINK,      // 闪烁
        'reverse'    => Color::REVERSE,    // 颠倒的 交换背景色与前景色
        'concealed'  => Color::CONCEALED,  // 隐匿的
    ];

    /**
     * Foreground color
     *
     * @var int
     */
    private $fgColor = 0;

    /**
     * Background color
     *
     * @var int
     */
    private $bgColor = 0;

    /**
     * Array of style options
     *
     * @var array
     */
    private $options = [];

    /**
     * @param string $fg
     * @param string $bg
     * @param array  $options
     * @param bool   $extra
     *
     * @return ColorCode
     * @throws InvalidArgumentException
     */
    public static function make($fg = '', $bg = '', array $options = [], bool $extra = false): ColorCode
    {
        return new self($fg, $bg, $options, $extra);
    }

    /**
     * Create a color style from a parameter string.
     *
     * @param string $string e.g 'fg=white;bg=black;options=bold,underscore;extra=1'
     *
     * @return static
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function fromString(string $string)
    {
        $extra   = false;
        $options = [];
        $parts   = explode(';', str_replace(' ', '', $string));

        $fg = $bg = '';
        foreach ($parts as $part) {
            $subParts = explode('=', $part);
            if (count($subParts) < 2) {
                continue;
            }

            switch ($subParts[0]) {
                case 'fg':
                    $fg = $subParts[1];
                    break;
                case 'bg':
                    $bg = $subParts[1];
                    break;
                case 'extra':
                    $extra = (bool)$subParts[1];
                    break;
                case 'options':
                    $options = explode(',', $subParts[1]);
                    break;
                default:
                    throw new RuntimeException('Invalid option');
                    break;
            }
        }

        return new self($fg, $bg, $options, $extra);
    }

    /**
     * Constructor
     *
     * @param string $fg      Foreground color.  e.g 'white'
     * @param string $bg      Background color.  e.g 'black'
     * @param array  $options Style options. e.g ['bold', 'underscore']
     * @param bool   $extra
     *
     * @throws InvalidArgumentException
     */
    public function __construct($fg = '', $bg = '', array $options = [], bool $extra = false)
    {
        if ($fg) {
            if (!isset(self::KNOWN_COLORS[$fg])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid foreground color "%1$s" [%2$s]',
                    $fg,
                    implode(', ', self::getKnownColors())
                ));
            }

            $this->fgColor = ($extra ? self::FG_EXTRA : self::FG_BASE) + self::KNOWN_COLORS[$fg];
        }

        if ($bg) {
            if (!isset(self::KNOWN_COLORS[$bg])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid background color "%1$s" [%2$s]',
                    $bg,
                    implode(', ', self::getKnownColors())
                ));
            }

            $this->bgColor = ($extra ? self::BG_EXTRA : self::BG_BASE) + self::KNOWN_COLORS[$bg];
        }

        foreach ($options as $option) {
            if (!isset(self::KNOWN_OPTIONS[$option])) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid option "%1$s" [%2$s]',
                    $option,
                    implode(', ', self::getKnownOptions())
                ));
            }

            $this->options[] = $option;
        }
    }

    /**
     * Convert to a string.
     */
    public function __toString()
    {
        return $this->toStyle();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->toStyle();
    }

    /**
     * Get the translated color code.
     *
     * @return string
     */
    public function toStyle(): string
    {
        $values = [];

        if ($this->fgColor) {
            $values[] = $this->fgColor;
        }

        if ($this->bgColor) {
            $values[] = $this->bgColor;
        }

        foreach ($this->options as $option) {
            $values[] = self::KNOWN_OPTIONS[$option];
        }

        return implode(';', $values);
    }

    /**
     * @param bool $onlyName
     *
     * @return array
     */
    public static function getKnownColors(bool $onlyName = true): array
    {
        return $onlyName ? array_keys(self::KNOWN_COLORS) : self::KNOWN_COLORS;
    }

    /**
     * @param bool $onlyName
     *
     * @return array
     */
    public static function getKnownOptions(bool $onlyName = true): array
    {
        return $onlyName ? array_keys(self::KNOWN_OPTIONS) : self::KNOWN_OPTIONS;
    }
}
