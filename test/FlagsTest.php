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
use Toolkit\Cli\Flags;
use function explode;

/**
 * Class FlagsTest
 *
 * @package Toolkit\CliTest
 */
class FlagsTest extends TestCase
{
    public function testParseArgv(): void
    {
        $rawArgv = explode(' ', 'git:tag --only-tag -d ../view arg0');

        [$args, $sOpts, $lOpts] = Flags::parseArgv($rawArgv);

        $this->assertNotEmpty($args);
        $this->assertSame('git:tag', $args[0]);
        $this->assertSame('arg0', $args[1]);

        $this->assertSame('../view', $sOpts['d']);
        $this->assertTrue($lOpts['only-tag']);

        [$args, $opts] = Flags::parseArgv($rawArgv, ['mergeOpts' => true]);

        $this->assertNotEmpty($args);
        $this->assertSame('git:tag', $args[0]);
        $this->assertSame('arg0', $args[1]);

        $this->assertSame('../view', $opts['d']);
        $this->assertTrue($opts['only-tag']);
    }

    public function testParseInvalidArgName(): void
    {
        [$args, , ] = Flags::parseArgv([
            'cmd',
            'http://some.com/path/to/merge_requests/new?utf8=%E2%9C%93&merge_request%5Bsource_project_id%5D=319'
        ]);

        $this->assertSame('cmd', $args[0]);
        $this->assertSame('http://some.com/path/to/merge_requests/new?utf8=%E2%9C%93&merge_request%5Bsource_project_id%5D=319', $args[1]);
    }

    public function testisParseWithSpace(): void
    {
        [$args, , ] = Flags::parseArgv([
            'cmd',
            ' -'
        ]);

        $this->assertSame('cmd', $args[0]);
        $this->assertSame(' -', $args[1]);
    }

    public function testisValidArgName(): void
    {
        $this->assertTrue(Flags::isValidArgName('arg0'));
        $this->assertFalse(Flags::isValidArgName('/path/to'));
    }
}
