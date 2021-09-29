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
use Toolkit\Cli\Flags;
use Toolkit\Cli\Helper\FlagHelper;
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

    public function testParseWithSpace(): void
    {
        [$args, , ] = Flags::parseArgv([
            'cmd',
            ' -'
        ]);

        $this->assertSame('cmd', $args[0]);
        $this->assertSame(' -', $args[1]);
    }

    public function testStopParseOnTwoHl(): void
    {
        [$args, , ] = Flags::parseArgv([
            '-n',
            'inhere',
            '--',
            '--age',
            '99',
            'cmd',
            ' -'
        ]);

        $this->assertSame('--age', $args[0]);
        $this->assertCount(4, $args);
    }
}
