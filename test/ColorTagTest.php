<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2019-01-08
 * Time: 00:01
 */

namespace Toolkit\CliTest;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\ColorTag;

/**
 * Class ColorTagTest
 * @package Toolkit\CliTest
 */
class ColorTagTest extends TestCase
{
    public function testStrip()
    {
        $text = ColorTag::strip('<tag>text</tag>');
        $this->assertSame('text', $text);

        // no close
        $text = ColorTag::clear('<tag>text<tag>');
        $this->assertSame('<tag>text<tag>', $text);
    }

    public function testWrap()
    {
        $text = ColorTag::wrap('text', 'tag');
        $this->assertSame('<tag>text</tag>', $text);

        $text = ColorTag::add('text', '');
        $this->assertSame('text', $text);

        $text = ColorTag::add('', 'tag');
        $this->assertSame('', $text);
    }

    public function testExists()
    {
        $this->assertTrue(ColorTag::exists('<tag>text</tag>'));
        $this->assertFalse(ColorTag::exists('text'));
        $this->assertFalse(ColorTag::exists('<tag>text'));
        $this->assertFalse(ColorTag::exists('<tag>text<tag>'));
    }
}
