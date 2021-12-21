<?php declare(strict_types=1);

namespace Toolkit\CliTest\Util;

use Toolkit\Cli\Util\Clog;
use Toolkit\CliTest\BaseCliTest;

/**
 * class ClogTest
 *
 * @author inhere
 */
class ClogTest extends BaseCliTest
{
    public function testClog_basic(): void
    {
        Clog::info("info message");
        Clog::error("error message");
        Clog::emerg("emerg message");
        Clog::log('warning', 'warning message');

        $this->assertNotEmpty(Clog::std());
        $this->assertNotEmpty(Clog::getLevelNames());
    }
}
