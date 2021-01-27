<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use function count;
use function explode;
use function ltrim;
use function strlen;
use function substr;

/**
 * Class LineParser
 *
 * @package Inhere\Console\Util
 */
class LineParser
{
    /**
     * full command line string.
     * eg: 'kite git acp -m "feat: support start an interactive shell for run application"'
     *
     * @var string
     */
    private $line;

    /**
     * the exploded nodes by space.
     *
     * @var array
     */
    private $nodes = [];

    /**
     * the parsed args
     *
     * @var array
     */
    private $args = [];

    /**
     * @param string $line full command line string.
     *
     * @return array
     */
    public static function parseIt(string $line): array
    {
        return (new self($line))->parse();
    }

    /**
     * Class constructor.
     *
     * @param string $line full command line string.
     */
    public function __construct(string $line)
    {
        $this->setLine($line);
    }

    /**
     * @return array
     */
    public function parse(): array
    {
        if ('' === $this->line) {
            return [];
        }

        $this->nodes = explode(' ', $this->line);
        if (count($this->nodes) === 1) {
            $this->args = $this->nodes;
            return $this->args;
        }

        $quoteChar = '';
        $fullItem  = '';
        foreach ($this->nodes as $item) {
            if ('' === $item) {
                continue;
            }

            $goon  = true;
            $start = $item[0];

            $len = strlen($item);
            $end = $item[$len - 1];

            // $start is start char
            if ($start === "'" || $start === '"') {
                $item = substr($item, 1);
                if ($quoteChar === $start) {
                    $this->args[] = $fullItem . ' ' . $item;
                    // must clear
                    $quoteChar = $fullItem = '';
                } else { // start
                    if ($fullItem) {
                        $this->args[] = $fullItem;
                    }

                    $quoteChar = $start;
                    $fullItem  = $item;
                }

                $goon = false;
            }

            // $end is end char
            if ($end === "'" || $end === '"') {
                $item = substr($item, 0, -1);
                if ($quoteChar === $end) {
                    $this->args[] = $fullItem . ' ' . $item;
                    // must clear
                    $quoteChar = $fullItem = '';
                } else {
                    if ($fullItem) {
                        $this->args[] = $fullItem;
                    }

                    $fullItem = $item;
                }

                $goon = false;
            }

            if ($goon === true) {
                if ($quoteChar) {
                    $fullItem .= ' ' . $item;
                } else {
                    $this->args[] = $item;
                }
            }
        }

        if ($fullItem) {
            $this->args[] = $fullItem;
        }

        return $this->args;
    }

    /**
     * @return string
     */
    public function getLine(): string
    {
        return $this->line;
    }

    /**
     * @param string $line
     */
    public function setLine(string $line): void
    {
        $this->line = ltrim($line);
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }
}
