<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\CliTest;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Color;
use Toolkit\Cli\Cli;

/**
 * Class ColorTest
 *
 * @package Toolkit\CliTest
 */
class ColorTest extends TestCase
{
    public function testRender(): void
    {
        if (!Cli::isSupportColor()) {
            return;
        }

        $text = Color::render('text', 'info');
        $this->assertStringContainsString(Color::STYLES['info'], $text);

        $text = Color::render('text', [Color::RESET, Color::FG_CYAN]);
        $this->assertStringContainsString(Color::STYLES['cyan'], $text);

        $text = Color::render('<info>text</info>');
        $this->assertStringContainsString(Color::STYLES['info'], $text);

        $text = Color::render('<light_blue>text</light_blue>');
        $this->assertStringContainsString(Color::STYLES['light_blue'], $text);

        $text = Color::render('<lightBlue>text</lightBlue>');
        $this->assertStringContainsString(Color::STYLES['lightBlue'], $text);
    }

    public function testApply(): void
    {
        if (!Cli::isSupportColor()) {
            return;
        }

        $text = Color::apply('info', 'text');
        $this->assertStringContainsString(Color::STYLES['info'], $text);

        foreach (Color::STYLES as $name => $code) {
            $text = Color::apply($name, 'text');
            $this->assertStringContainsString($code, $text);
        }
    }
}
