<?php declare(strict_types=1);


namespace Toolkit\Cli\Color;

use Toolkit\Cli\Color;
use function implode;
use function is_array;
use function sprintf;
use function strtoupper;

/**
 * Class Block
 * @package Toolkit\Cli\Color
 */
class Alert
{
    /** @var self */
    public static $global;

    protected $styles = [
        'info'    => 'info',
        'warn'    => 'warning',
        'warning' => 'warning',
        'debug'   => 'cyan',
        // 'notice'  => 'notice',
        'error'   => [Color::FG_WHITE, Color::BG_RED],
    ];

    /**
     * @var string
     */
    private $style;

    /**
     * @var string
     */
    private $title;

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
     * Alert constructor.
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

        echo Color::render($title . sprintf($fmt, ...$args), $this->style);
    }

    /**
     * @param mixed $message
     *
     * @return string
     */
    public function sprint($message): string
    {
        $title = strtoupper($this->title) . ': ';
        if (is_array($message)) {
            $message = implode(' ', $message);
        }

        return Color::render($title . $message, $this->style);
    }

    /**
     * @param mixed $message
     */
    public function println($message): void
    {
        $title = strtoupper($this->title) . ': ';
        if (is_array($message)) {
            $message = implode(' ', $message);
        }

        echo Color::render($title . $message, $this->style), "\n";
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
