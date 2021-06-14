<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use InvalidArgumentException;
use Toolkit\Cli\Color;
use function array_merge;
use function array_slice;
use function defined;
use function explode;
use function file_get_contents;
use function function_exists;
use function implode;
use function is_array;
use function max;
use function str_pad;
use function str_replace;
use function token_get_all;
use const PHP_EOL;
use const STR_PAD_LEFT;
use const T_CLASS_C;
use const T_CLOSE_TAG;
use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DIR;
use const T_DNUMBER;
use const T_DOC_COMMENT;
use const T_ENCAPSED_AND_WHITESPACE;
use const T_FILE;
use const T_FUNC_C;
use const T_INLINE_HTML;
use const T_LINE;
use const T_LNUMBER;
use const T_METHOD_C;
use const T_NS_C;
use const T_OPEN_TAG;
use const T_OPEN_TAG_WITH_ECHO;
use const T_STRING;
use const T_TRAIT_C;
use const T_VARIABLE;
use const T_WHITESPACE;

/**
 * Class Highlighter
 *
 * @package Toolkit\Cli
 * @see     jakub-onderka/php-console-highlighter
 * @link    https://github.com/JakubOnderka/PHP-Console-Highlighter/blob/master/src/Highlighter.php
 */
class Highlighter
{
    public const TOKEN_DEFAULT = 'token_default';

    public const TOKEN_COMMENT = 'token_comment';

    public const TOKEN_STRING = 'token_string';

    public const TOKEN_HTML = 'token_html';

    public const TOKEN_KEYWORD = 'token_keyword';

    public const TOKEN_CONSTANT = 'token_constant';

    public const TOKEN_VARIABLE = 'token_variable';

    public const ACTUAL_LINE_MARK = 'actual_line_mark';

    public const LINE_NUMBER = 'line_number';

    // @var Style
    //private $color;

    /** @var self */
    private static $instance;

    /**
     * @var array
     */
    private $codeTheme = [
        self::TOKEN_STRING     => 'green',
        self::TOKEN_COMMENT    => 'italic',
        self::TOKEN_KEYWORD    => 'yellow',
        self::TOKEN_DEFAULT    => 'normal',
        self::TOKEN_CONSTANT   => 'red',
        self::TOKEN_HTML       => 'cyan',
        self::TOKEN_VARIABLE   => 'cyan',
        self::ACTUAL_LINE_MARK => 'red',
        self::LINE_NUMBER      => 'darkGray',
    ];

    /** @var bool */
    private $hasTokenFunc;

    /**
     * @return Highlighter
     */
    public static function create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->hasTokenFunc = function_exists('token_get_all');
    }

    /**
     * highlight a full php file content
     *
     * @param string $source
     * @param bool   $withLineNumber with line number
     *
     * @return string
     */
    public function highlight(string $source, bool $withLineNumber = false): string
    {
        $tokenLines = $this->getHighlightedLines($source);
        $lines      = $this->colorLines($tokenLines);

        if ($withLineNumber) {
            return $this->lineNumbers($lines);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param string $file
     * @param bool   $withLineNumber
     *
     * @return string
     */
    public function highlightFile(string $file, bool $withLineNumber = false): string
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException("the target file is not exist! file: $file");
        }

        $source = file_get_contents($file);

        return $this->highlight($source, $withLineNumber);
    }

    /**
     * @param string $source
     * @param int    $lineNumber
     * @param int    $linesBefore
     * @param int    $linesAfter
     *
     * @return string
     */
    public function snippet(string $source, int $lineNumber, int $linesBefore = 2, int $linesAfter = 2): string
    {
        return $this->highlightSnippet($source, $lineNumber, $linesBefore, $linesAfter);
    }

    /**
     * @param string $source
     * @param int    $lineNumber
     * @param int    $linesBefore
     * @param int    $linesAfter
     *
     * @return string
     */
    public function highlightSnippet(string $source, int $lineNumber, int $linesBefore = 2, int $linesAfter = 2): string
    {
        $tokenLines = $this->getHighlightedLines($source);

        $offset = $lineNumber - $linesBefore - 1;
        $offset = max($offset, 0);
        $length = $linesAfter + $linesBefore + 1;

        $tokenLines = array_slice($tokenLines, $offset, $length, true);

        $lines = $this->colorLines($tokenLines);

        return $this->lineNumbers($lines, $lineNumber);
    }

    /**
     * @param string $source
     *
     * @return array
     */
    private function getHighlightedLines(string $source): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);

        if ($this->hasTokenFunc) {
            $tokens = $this->tokenize($source);
            return $this->splitToLines($tokens);
        }

        // if no func: token_get_all
        return explode("\n", $source);
    }

    /**
     * @param string $source
     *
     * @return array
     */
    private function tokenize(string $source): array
    {
        $buffer  = '';
        $output  = [];
        $tokens  = token_get_all($source);
        $newType = $currentType = null;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_INLINE_HTML:
                        $newType = self::TOKEN_HTML;
                        break;
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        $newType = self::TOKEN_COMMENT;
                        break;
                    case T_ENCAPSED_AND_WHITESPACE:
                    case T_CONSTANT_ENCAPSED_STRING:
                        $newType = self::TOKEN_STRING;
                        break;
                    case T_WHITESPACE:
                        break;
                    case T_OPEN_TAG:
                    case T_OPEN_TAG_WITH_ECHO:
                    case T_CLOSE_TAG:
                    case T_STRING:
                        // Constants
                        // case T_DIR:
                        // case T_FILE:
                    case T_METHOD_C:
                    case T_DNUMBER:
                    case T_LNUMBER:
                    case T_NS_C:
                    case T_LINE:
                    case T_CLASS_C:
                    case T_FUNC_C:
                        //case T_TRAIT_C:
                        $newType = self::TOKEN_DEFAULT;
                        break;
                    // Constants
                    case T_DIR:
                    case T_FILE:
                        $newType = self::TOKEN_CONSTANT;
                        break;
                    case T_VARIABLE:
                        $newType = self::TOKEN_VARIABLE;
                        break;
                    default:
                        // Compatibility with PHP 5.3
                        if (defined('T_TRAIT_C') && $token[0] === T_TRAIT_C) {
                            $newType = self::TOKEN_DEFAULT;
                        } else {
                            $newType = self::TOKEN_KEYWORD;
                        }
                }
            } else {
                $newType = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
            }

            if ($currentType === null) {
                $currentType = $newType;
            }

            if ($currentType !== $newType) {
                $output[]    = [$currentType, $buffer];
                $buffer      = '';
                $currentType = $newType;
            }

            $buffer .= is_array($token) ? $token[1] : $token;
        }

        if (null !== $newType) {
            $output[] = [$newType, $buffer];
        }

        return $output;
    }

    /**
     * @param array $tokens
     *
     * @return array
     */
    private function splitToLines(array $tokens): array
    {
        $lines = $line = [];

        foreach ($tokens as $token) {
            foreach (explode("\n", $token[1]) as $count => $tokenLine) {
                if ($count > 0) {
                    $lines[] = $line;
                    $line    = [];
                }
                if ($tokenLine === '') {
                    continue;
                }

                $line[] = [$token[0], $tokenLine];
            }
        }
        $lines[] = $line;

        return $lines;
    }

    /**
     * @param array[] $tokenLines
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function colorLines(array $tokenLines): array
    {
        if (!$this->hasTokenFunc) {
            return $tokenLines;
        }

        $lines = [];

        foreach ($tokenLines as $lineCount => $tokenLine) {
            $line = '';
            foreach ($tokenLine as [$tokenType, $tokenValue]) {
                $style = $this->codeTheme[$tokenType];

                if (Color::hasStyle($style)) {
                    $line .= Color::apply($style, $tokenValue);
                } else {
                    $line .= $tokenValue;
                }
            }

            $lines[$lineCount] = $line;
        }

        return $lines;
    }

    /**
     * @param array    $lines
     * @param null|int $markLine
     *
     * @return string
     */
    private function lineNumbers(array $lines, int $markLine = null): string
    {
        $snippet = '';
        $lineLen = count($lines) + 1;
        $lmStyle = $this->codeTheme[self::ACTUAL_LINE_MARK];
        $lnStyle = $this->codeTheme[self::LINE_NUMBER];

        foreach ($lines as $i => $lineTxt) {
            $lineNum = $i + 1;
            $lineStr = (string)($lineNum);
            $numText = str_pad($lineStr, $lineLen, ' ', STR_PAD_LEFT) . '| ';
            if ($markLine !== null) {
                $snippet .= ($markLine === $lineNum ? Color::apply($lmStyle, '  > ') : '    ');
                $snippet .= Color::apply($markLine === $lineNum ? $lmStyle : $lnStyle, $numText);
            } else {
                $snippet .= Color::apply($lnStyle, $numText);
            }

            $snippet .= $lineTxt . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @return array
     */
    public function getCodeTheme(): array
    {
        return $this->codeTheme;
    }

    /**
     * @param array $codeTheme
     */
    public function setCodeTheme(array $codeTheme): void
    {
        $this->codeTheme = array_merge($this->codeTheme, $codeTheme);
    }
}
