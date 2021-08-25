<?php declare(strict_types=1);

namespace Toolkit\CliTest\Helper;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\Helper\FlagHelper;

/**
 * class FlagHelperTest
 */
class FlagHelperTest extends TestCase
{
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
        ];

        foreach ($tests as $name => $ok) {
            $this->assertSame($ok, FlagHelper::isValidName($name));
        }
    }
}
