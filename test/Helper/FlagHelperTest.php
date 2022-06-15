<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\CliTest\Helper;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Helper\FlagHelper;
use function printf;

/**
 * class FlagHelperTest
 */
class FlagHelperTest extends TestCase
{
    public function testIsValidArgName1(): void
    {
        $this->assertTrue(FlagHelper::isValidName('arg0'));
        $this->assertFalse(FlagHelper::isValidName('9'));
        $this->assertFalse(FlagHelper::isValidName('/path/to'));
    }

    public function testIsOptionValue(): void
    {
        $this->assertTrue(FlagHelper::isOptionValue('arg0'));
        $this->assertTrue(FlagHelper::isOptionValue('arg0-'));
        $this->assertTrue(FlagHelper::isOptionValue('-'));
        $this->assertTrue(FlagHelper::isOptionValue('--'));

        $this->assertFalse(FlagHelper::isOptionValue('-d'));
        $this->assertFalse(FlagHelper::isOptionValue('--opt'));
    }

    public function testIsValidName(): void
    {
        $tests = [
            'name'      => true,
            'name1'     => true,
            'some-name' => true,
            'some_name' => true,
            'someName'  => true,
            'SomeName'  => true,
            '_2'        => true,
            '_someName' => true,
            '_SomeName' => true,
            '_name-'    => true,
            '-name'     => false,
            ' name'     => false,
            '+name'     => false,
            3           => false,
            30          => false,
        ];

        foreach ($tests as $name => $ok) {
            $this->assertSame($ok, FlagHelper::isValidName((string)$name));
        }
    }

    public function testParseArgv(): void
    {
        $str = 'git:tag --only-tag -d ../view arg0';
        printf("parse: %s\n", $str);
        $rawArgv = explode(' ', $str);

        [$args, $sOpts, $lOpts] = FlagHelper::parseArgv($rawArgv);

        $this->assertNotEmpty($args);
        $this->assertSame('git:tag', $args[0]);
        $this->assertSame('arg0', $args[1]);

        $this->assertSame('../view', $sOpts['d']);
        $this->assertTrue($lOpts['only-tag']);

        [$args, $opts] = FlagHelper::parseArgv($rawArgv, ['mergeOpts' => true]);

        $this->assertNotEmpty($args);
        $this->assertSame('git:tag', $args[0]);
        $this->assertSame('arg0', $args[1]);

        $this->assertSame('../view', $opts['d']);
        $this->assertTrue($opts['only-tag']);
    }

    public function testParseInvalidArgName(): void
    {
        [$args, , ] = FlagHelper::parseArgv([
            'cmd',
            'http://some.com/path/to/merge_requests/new?utf8=%E2%9C%93&merge_request%5Bsource_project_id%5D=319'
        ]);

        $this->assertSame('cmd', $args[0]);
        $this->assertSame('http://some.com/path/to/merge_requests/new?utf8=%E2%9C%93&merge_request%5Bsource_project_id%5D=319', $args[1]);
    }

    public function testParseWithSpace(): void
    {
        [$args, , ] = FlagHelper::parseArgv([
            'cmd',
            ' -'
        ]);

        $this->assertSame('cmd', $args[0]);
        $this->assertSame(' -', $args[1]);
    }

    public function testStopParseOnTwoHl(): void
    {
        [$args, , ] = FlagHelper::parseArgv([
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
