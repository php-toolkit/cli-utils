<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

use Toolkit\Cli\Color;

require dirname(__DIR__) . '/test/bootstrap.php';

foreach (Color::getStyles() as $style) {
    printf("    %s: %s\n", $style, Color::apply($style, 'This is a message'));
}
