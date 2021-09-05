<?php
/**
 * https://www.sitepoint.com/howd-they-do-it-phpsnake-detecting-keypresses/
 */

function test_run()
{
    echo "Hello, I am snake!";

    system('stty cbreak -echo');
    $stdin = fopen('php://stdin', 'rb');

    while (1) {
        $c = ord(fgetc($stdin));

        echo "Char read: $c\n";
    }
}

require dirname(__DIR__) . '/test/bootstrap.php';

test_run();

