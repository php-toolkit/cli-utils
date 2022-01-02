<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

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
        Clog::info('info message');
        Clog::error('error message');
        Clog::emerg('emerg message');
        Clog::log('warning', 'warning message');

        $this->assertNotEmpty(Clog::std());
        $this->assertNotEmpty(Clog::getLevelNames());
    }
}
