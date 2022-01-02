<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

function test_run(): void
{
    echo 'Hello, I am snake!';

    system('stty cbreak -echo');
    $stdin = fopen('php://stdin', 'rb');

    while (1) {
        $c = ord(fgetc($stdin));

        echo "Char read: $c\n";
    }
}

require dirname(__DIR__) . '/test/bootstrap.php';

test_run();
