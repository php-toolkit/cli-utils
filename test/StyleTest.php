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
