<?php declare(strict_types=1);


namespace Toolkit\CliTest;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Style;

/**
 * Class StyleTest
 * @package Toolkit\CliTest
 */
class StyleTest extends TestCase
{
    public function testBasic(): void
    {
        $str = Style::global()->render('<info>he</info>llo');

        self::assertNotEmpty($str);
    }
}
