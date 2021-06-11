<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\CliTest\Util;

use Toolkit\Cli\Util\LineParser;
use PHPUnit\Framework\TestCase;
use function count;

/**
 * Class LineParserTest
 * @package Toolkit\CliTest\Util
 */
class LineParserTest extends TestCase
{
    public function testParse(): void
    {
        $line = 'kite git status';
        $args = LineParser::parseIt($line);
        self::assertCount(3, $args);

        $line = 'kite git commit -m "the commit message"';
        $args = LineParser::parseIt($line);
        self::assertCount(5, $args);
        $len = count($args);
        self::assertSame('the commit message', $args[$len-1]);

        $line = 'kite git commit -m "the commit message';
        $args = LineParser::parseIt($line);
        self::assertCount(5, $args);
        $len = count($args);
        self::assertSame('the commit message', $args[$len-1]);

        $line = 'kite top sub -a "the a message" --foo val1 --bar "val 2"';
        $args = LineParser::parseIt($line);
        self::assertCount(9, $args);
        $len = count($args);
        self::assertSame('val 2', $args[$len-1]);

        $line = 'kite top sub -a "the a message " --foo val1 --bar "val 2"';
        $args = LineParser::parseIt($line);
        self::assertCount(9, $args);
        $len = count($args);
        self::assertSame('val 2', $args[$len-1]);

        $line = 'kite top sub -a "the a message "foo val1 --bar "val 2"';
        $args = LineParser::parseIt($line);
        self::assertCount(8, $args);
    }
}
