<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\CliTest\Color;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Color\ColorCode;

class ColorCodeTest extends TestCase
{
    public function testSome(): void
    {
        $tests = [
            'fg=green'                     => '32',
            'fg=green;extra=1'             => '92',
            'fg=green;options=bold,italic' => '32;1;3',
        ];

        foreach ($tests as $str => $wantCode) {
            $code = ColorCode::fromString($str);
            $this->assertSame($wantCode, $code->toString());
        }
    }
}
