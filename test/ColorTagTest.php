<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\CliTest;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Color;
use Toolkit\Cli\ColorTag;
use function strpos;
use function vdump;
use const PHP_EOL;

/**
 * Class ColorTagTest
 *
 * @package Toolkit\CliTest
 */
class ColorTagTest extends TestCase
{
    public function testMatchAll(): void
    {
        $ret = ColorTag::matchAll('<tag>text0</tag> or <info>text1</info>');
        $this->assertCount(3, $ret);
        // tag
        $this->assertSame('tag', $ret[1][0]);
        $this->assertSame('info', $ret[1][1]);
        // content
        $this->assertSame('text0', $ret[2][0]);

        $ret = ColorTag::matchAll('<some_tag>text</some_tag>');
        $this->assertCount(3, $ret);
        // tag
        $this->assertSame('some_tag', $ret[1][0]);
        // content
        $this->assertSame('text', $ret[2][0]);

        $ret = ColorTag::matchAll('<someTag>text</someTag>');
        $this->assertCount(3, $ret);
        // tag
        $this->assertSame('someTag', $ret[1][0]);
        // content
        $this->assertSame('text', $ret[2][0]);
    }

    public function testStrip(): void
    {
        $text = ColorTag::strip('<tag>text</tag>');
        $this->assertSame('text', $text);

        // no close
        $text = ColorTag::clear('<tag>text<tag>');
        $this->assertSame('<tag>text<tag>', $text);
    }

    public function testWrap(): void
    {
        $text = ColorTag::wrap('text', 'tag');
        $this->assertSame('<tag>text</tag>', $text);

        $text = ColorTag::add('text', '');
        $this->assertSame('text', $text);

        $text = ColorTag::add('', 'tag');
        $this->assertSame('', $text);
    }

    public function testExists(): void
    {
        $this->assertTrue(ColorTag::exists('<tag>text</tag>'));
        $this->assertFalse(ColorTag::exists('text'));
        $this->assertFalse(ColorTag::exists('<tag>text'));
        $this->assertFalse(ColorTag::exists('<tag>text<tag>'));
    }

    public function testParse(): void
    {
        Color::setForceColor(true);

        $text = ColorTag::parse('<info>INFO</info>');
        echo $text, PHP_EOL;
        $this->assertSame("\033[0;32mINFO\033[0m", $text);

        // multi
        $text = ColorTag::parse('multi: <info>INFO</info> <cyan>CYAN</cyan> <red>RED</red>');
        echo $text, PHP_EOL;
        $this->assertFalse(strpos($text, '</'));

        // nested Tags
        $text = ColorTag::parse('nested: <info>INFO <cyan>CYAN</cyan></info>');
        echo $text, PHP_EOL;
        $this->assertTrue(strpos($text, '</') > 0);
        $this->assertSame("nested: \033[0;32mINFO <cyan>CYAN</cyan>\033[0m", $text);

        Color::resetConfig();
    }

    public function testParseNestTag(): void
    {
        Color::setForceColor(true);

        // nested Tags
        $text = ColorTag::parse('<info>INFO <cyan>CYAN mess</cyan>age</info>', true);
        echo 'nested: ' . $text, PHP_EOL;
        $this->assertSame("\033[0;32mINFO \033[0;36mCYAN mess\033[0mage\033[0m", $text);

        Color::resetConfig();
    }
}
