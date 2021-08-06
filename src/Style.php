<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli;

use InvalidArgumentException;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function is_array;
use function is_object;
use function sprintf;
use function strpos;

/**
 * Class Style
 *
 * @package Inhere\Console\Component\Style
 * @link    https://github.com/ventoviro/windwalker-IO
 *
 * @method string info(string $message)
 * @method string comment(string $message)
 * @method string success(string $message)
 * @method string warning(string $message)
 * @method string danger(string $message)
 * @method string error(string $message)
 */
class Style
{
    /**
     * there are some default style tags
     */
    public const NORMAL = 'normal';

    public const FAINTLY = 'faintly';

    public const BOLD = 'bold';

    public const NOTICE = 'notice';

    public const PRIMARY = 'primary';

    public const SUCCESS = 'success';

    public const INFO = 'info';

    public const NOTE = 'note';

    public const WARNING = 'warning';

    public const COMMENT = 'comment';

    public const QUESTION = 'question';

    public const DANGER = 'danger';

    public const ERROR = 'error';

    /**
     * Regex to match tags
     *
     * @var string
     * @deprecated please use ColorTag::MATCH_TAG
     */
    public const COLOR_TAG = '/<([a-zA-Z0-9=;]+)>(.*?)<\/\\1>/s';

    /**
     * @var self
     */
    private static $instance;

    /**
     * Array of Color objects
     *
     * @var ColorCode[]
     */
    private $styles = [];

    /**
     * @return Style
     */
    public static function instance(): self
    {
        return self::global();
    }

    /**
     * @return Style
     */
    public static function global(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @param string $fg      前景色(字体颜色)
     * @param string $bg      背景色
     * @param array  $options 其它选项
     */
    public function __construct(string $fg = '', string $bg = '', array $options = [])
    {
        if ($fg || $bg || $options) {
            $this->add('base', $fg, $bg, $options);
        }

        $this->loadDefaultStyles();
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed|string
     * @throws InvalidArgumentException
     */
    public function __call(string $method, array $args)
    {
        if (isset($args[0]) && $this->hasStyle($method)) {
            return $this->format(sprintf('<%s>%s</%s>', $method, $args[0], $method));
        }

        throw new InvalidArgumentException("You called method is not exists: $method");
    }

    /**
     * Adds predefined color styles to the Color styles
     * default primary success info warning danger
     */
    protected function loadDefaultStyles(): void
    {
        $this->addByArray(self::NORMAL, ['fg' => 'normal'])
            // 不明显的 浅灰色的
             ->addByArray(self::FAINTLY, ['fg' => 'normal', 'options' => ['italic']])
             ->addByArray(self::BOLD, ['options' => ['bold']])
             ->addByArray(self::INFO, ['fg' => 'green',])//'options' => ['bold']
             ->addByArray(self::NOTE, ['fg' => 'cyan', 'options' => ['bold']])//'options' => ['bold']
             ->addByArray(self::PRIMARY, ['fg' => 'yellow', 'options' => ['bold']])//
             ->addByArray(self::SUCCESS, ['fg' => 'green', 'options' => ['bold']])
             ->addByArray(self::NOTICE, ['options' => ['bold', 'underscore'],])
             ->addByArray(self::WARNING, ['fg' => 'black', 'bg' => 'yellow',])//'options' => ['bold']
             ->addByArray(self::COMMENT, ['fg' => 'yellow',])//'options' => ['bold']
             ->addByArray(self::QUESTION, ['fg' => 'black', 'bg' => 'cyan'])
             ->addByArray(self::DANGER, ['fg' => 'red',])// 'bg' => 'magenta', 'options' => ['bold']
             ->add(self::ERROR, 'white', 'red', [], true)->add('underline', 'normal', '', ['underscore'])
             ->add('blue', 'blue')->add('cyan', 'cyan')->add('magenta', 'magenta')->add('mga', 'magenta')
             ->add('red', 'red')->addByArray('yellow', ['fg' => 'yellow'])
             ->addByArray('darkGray', ['fg' => 'black', 'extra' => true]);
    }

    /**
     * Process a string use style
     *
     * @param string $style
     * @param string $text
     *
     * @return string
     */
    public function apply(string $style, string $text): string
    {
        return $this->format(self::wrap($text, $style));
    }

    /**
     * Process a string.
     *
     * @param string $text
     *
     * @return mixed
     */
    public function t(string $text)
    {
        return $this->format($text);
    }

    /**
     * Process a string.
     *
     * @param string $text
     *
     * @return mixed
     */
    public function render(string $text)
    {
        return $this->format($text);
    }

    /**
     * @param string $text
     *
     * @return mixed|string
     */
    public function format(string $text)
    {
        if (!$text || false === strpos($text, '</')) {
            return $text;
        }

        // if don't support output color text, clear color tag.
        if (!Color::isShouldRenderColor()) {
            return self::stripColor($text);
        }

        if (!$matches = ColorTag::matchAll($text)) {
            return $text;
        }

        foreach ((array)$matches[0] as $i => $m) {
            $key = $matches[1][$i];

            if (array_key_exists($key, $this->styles)) {
                $text = ColorTag::replaceColor($text, $key, $matches[2][$i], (string)$this->styles[$key]);
            } elseif (isset(Color::STYLES[$key])) {
                $text = ColorTag::replaceColor($text, $key, $matches[2][$i], Color::STYLES[$key]);
                /** Custom style format @see ColorCode::fromString() */
            } elseif (strpos($key, '=')) {
                $text = ColorTag::replaceColor($text, $key, $matches[2][$i], (string)ColorCode::fromString($key));
            }
        }

        return $text;
    }

    /**
     * Strip color tags from a string.
     *
     * @param string $string
     *
     * @return mixed
     */
    public static function stripColor(string $string)
    {
        return ColorTag::strip($string);
    }

    /****************************************************************************
     * Attr Color Style
     ****************************************************************************/

    /**
     * Add a style.
     *
     * @param string                 $name
     * @param string|ColorCode|array $fg      前景色|Color对象|也可以是style配置数组(@see self::addByArray())
     *                                        当它为Color对象或配置数组时，后面两个参数无效
     * @param string                 $bg      背景色
     * @param array                  $options 其它选项
     * @param bool                   $extra
     *
     * @return $this
     */
    public function add(string $name, $fg = '', string $bg = '', array $options = [], bool $extra = false): self
    {
        if (is_array($fg)) {
            return $this->addByArray($name, $fg);
        }

        if (is_object($fg) && $fg instanceof ColorCode) {
            $this->styles[$name] = $fg;
        } else {
            $this->styles[$name] = ColorCode::make($fg, $bg, $options, $extra);
        }

        return $this;
    }

    /**
     * Add a style by an array config
     *
     * @param string $name
     * @param array  $styleConfig 样式设置信息
     *                            e.g
     *                            [
     *                            'fg' => 'white',
     *                            'bg' => 'black',
     *                            'extra' => true,
     *                            'options' => ['bold', 'underscore']
     *                            ]
     *
     * @return $this
     */
    public function addByArray(string $name, array $styleConfig): self
    {
        $style = [
            'fg'      => '',
            'bg'      => '',
            'extra'   => false,
            'options' => []
        ];

        $config = array_merge($style, $styleConfig);
        // expand
        [$fg, $bg, $extra, $options] = array_values($config);

        $this->styles[$name] = ColorCode::make($fg, $bg, $options, (bool)$extra);

        return $this;
    }

    /**
     * @return array
     */
    public function getStyleNames(): array
    {
        return array_keys($this->styles);
    }

    /**
     * @return array
     */
    public function getNames(): array
    {
        return array_keys($this->styles);
    }

    /**
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @param $name
     *
     * @return ColorCode|null
     */
    public function getStyle($name): ?ColorCode
    {
        return $this->styles[$name] ?? null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasStyle($name): bool
    {
        return isset($this->styles[$name]);
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
     * Method to get property NoColor
     */
    public static function isNoColor(): bool
    {
        return Color::isNoColor();
    }

    /**
     * Method to set property noColor
     *
     * @param bool $noColor
     */
    public static function setNoColor(bool $noColor = true): void
    {
        Color::setNoColor($noColor);
    }
}
