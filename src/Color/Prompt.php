<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Color;

use Toolkit\Cli\Color;
use function implode;
use function is_array;
use function sprintf;
use function strtoupper;

/**
 * Class Prompt
 * @package Toolkit\Cli\Color
 */
class Prompt
{
    /** @var self|null */
    public static ?Prompt $global = null;

    /**
     * @var string
     */
    private string $style;

    /**
     * @var string
     */
    private string $title;

    /**
     * @return static
     */
    public static function global(): self
    {
        if (!self::$global) {
            self::$global = new self();
        }

        return self::$global;
    }

    /**
     * @param string $style
     *
     * @return self
     */
    public static function new(string $style = 'info'): self
    {
        return new self($style);
    }

    /**
     * Prompt constructor.
     *
     * @param string $style
     */
    public function __construct(string $style = 'info')
    {
        $this->style = $style;
        $this->title = $style;
    }

    /**
     * @param string      $style
     * @param string|null $title
     *
     * @return $this
     */
    public function withStyle(string $style, ?string $title = null): self
    {
        if ($style) {
            $this->style = $style;
        }

        if (null !== $title) {
            $this->title = $title;
        }

        return $this;
    }

    /**
     * @param string $fmt
     * @param mixed  ...$args
     */
    public function printf(string $fmt, ...$args): void
    {
        $title = strtoupper($this->title) . ': ';

        echo Color::render($title, $this->style) . sprintf($fmt, ...$args);
    }

    /**
     * @param mixed $message
     *
     * @return string
     */
    public function sprint(mixed $message): string
    {
        $title = strtoupper($this->title) . ': ';
        if (is_array($message)) {
            $message = implode(' ', $message);
        }

        return Color::render($title, $this->style) . $message;
    }

    /**
     * @param mixed $message
     */
    public function println(mixed $message): void
    {
        $title = strtoupper($this->title) . ': ';
        if (is_array($message)) {
            $message = implode(' ', $message);
        }

        echo Color::render($title, $this->style) . $message, "\n";
    }

    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
