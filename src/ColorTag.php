<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strpos;

/**
 * Class ColorTag
 *
 * @package Toolkit\Cli
 */
class ColorTag
{
    // regex used for removing color tags
    public const STRIP_TAG = '/<[\/]?[a-zA-Z=;]+>/';

    // Regex to match tags/
    public const MATCH_TAG = '/<([a-zA-Z=;_]+)>(.*?)<\/\\1>/s';

    // color
    public const BLACK   = 'black';

    public const RED     = 'red';

    public const GREEN   = 'green';

    public const YELLOW  = 'yellow'; // BROWN

    public const BLUE    = 'blue';

    public const MAGENTA = 'magenta';

    public const CYAN    = 'cyan';

    public const WHITE   = 'white';

    public const NORMAL  = 'normal';

    // color option
    public const BOLD       = 'bold';       // 加粗

    public const FUZZY      = 'fuzzy';      // 模糊(不是所有的终端仿真器都支持)

    public const ITALIC     = 'italic';     // 斜体(不是所有的终端仿真器都支持)

    public const UNDERSCORE = 'underscore'; // 下划线

    public const BLINK      = 'blink';      // 闪烁

    public const REVERSE    = 'reverse';    // 颠倒的 交换背景色与前景色

    public const CONCEALED  = 'concealed';  // 隐匿的

    /**
     * Alias of the wrap()
     *
     * @param string $text
     * @param string $tag
     *
     * @return string
     */
    public static function add(string $text, string $tag): string
    {
        return self::wrap($text, $tag);
    }

    /**
     * wrap a color style tag
     *
     * @param string $text
     * @param string $tag
     *
     * @return string
     */
    public static function wrap(string $text, string $tag): string
    {
        if (!$text || !$tag) {
            return $text;
        }

        return "<$tag>$text</$tag>";
    }

    /**
     * @param string $text
     *
     * @return array
     */
    public static function matchAll(string $text): array
    {
        if (!preg_match_all(self::MATCH_TAG, $text, $matches)) {
            return [];
        }

        return $matches;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function parse(string $text): string
    {
        if (!$text || false === strpos($text, '</')) {
            return $text;
        }

        // shouldn't render color, clear color code.
        if (!Color::isShouldRenderColor()) {
            return self::strip($text);
        }

        // match color tags
        if (!$matches = self::matchAll($text)) {
            return $text;
        }

        foreach ((array)$matches[0] as $i => $m) {
            $key = $matches[1][$i];

            if (isset(Color::STYLES[$key])) {
                $text = self::replaceColor($text, $key, $matches[2][$i], Color::STYLES[$key]);

            /** Custom style format @see Color::stringToCode() */
            } elseif (strpos($key, '=')) {
                $text = self::replaceColor($text, $key, $matches[2][$i], Color::stringToCode($key));
            }
        }

        return $text;
    }

    /**
     * Replace color tags in a string.
     *
     * @param string $text
     * @param string $tag       The matched tag.
     * @param string $match     The matched text
     * @param string $colorCode The color style to apply.
     *
     * @return  string
     */
    public static function replaceColor(string $text, string $tag, string $match, string $colorCode): string
    {
        $replace = sprintf("\033[%sm%s\033[0m", $colorCode, $match);

        return str_replace("<$tag>$match</$tag>", $replace, $text);
        // return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }

    /**
     * Exists color tags
     *
     * @param string $text
     *
     * @return bool
     */
    public static function exists(string $text): bool
    {
        return strpos($text, '</') > 0;
    }

    /**
     * Alias of the strip()
     *
     * @param string $text
     *
     * @return string
     */
    public static function clear(string $text): string
    {
        return self::strip($text);
    }

    /**
     * Strip color tags from a string.
     *
     * @param string $text
     *
     * @return mixed
     */
    public static function strip(string $text): string
    {
        if (false === strpos($text, '</')) {
            return $text;
        }

        // $text = \strip_tags($text);
        return preg_replace(self::STRIP_TAG, '', $text);
    }
}
