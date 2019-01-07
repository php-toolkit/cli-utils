<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-07
 * Time: 23:52
 */

namespace Toolkit\Cli;

/**
 * Class ColorTag
 * @package Toolkit\Cli
 */
class ColorTag
{
    // regex used for removing color tags
    private const STRIP_TAG = '/<[\/]?[a-zA-Z=;]+>/';

    /**
     * alias of the wrap()
     * @param string $text
     * @return string
     */
    public static function add(string $text, string $tag): string
    {
        return self::wrap($text, $tag);
    }

    /**
     * wrap a color style tag
     * @param string $text
     * @param string $tag
     * @return string
     */
    public static function wrap(string $text, string $tag): string
    {
        if (!$text || !$tag) {
            return $text;
        }

        return "<$tag>$text</$tag>";
    }

    public static function parse(string $text): string
    {

    }

    /**
     * exists color tags
     * @param string $text
     * @return bool
     */
    public static function exists(string $text): bool
    {
        return false !== \strpos($text, '</');
    }

    /**
     * alias of the strip()
     * @param string $text
     * @return string
     */
    public static function clear(string $text): string
    {
        return self::strip($text);
    }

    /**
     * Strip color tags from a string.
     * @param string $text
     * @return mixed
     */
    public static function strip(string $text): string
    {
        if (false === \strpos($text, '</')) {
            return $text;
        }

        // $text = \strip_tags($text);
        return \preg_replace(self::STRIP_TAG, '', $text);
    }
}
