<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

use Toolkit\Cli\Util\Clog;

require dirname(__DIR__) . '/test/bootstrap.php';

// run: php example/log.php
foreach (Clog::getLevelNames() as $level) {
    Clog::log($level, "example log $level message");
}
