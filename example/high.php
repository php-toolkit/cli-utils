<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

use Toolkit\Cli\Cli;
use Toolkit\Cli\Util\Highlighter;

require dirname(__DIR__) . '/test/boot.php';

echo "Highlight current file content:\n";

// this is an comment
$rendered = Highlighter::create()->highlight(file_get_contents(__FILE__));

Cli::write($rendered);
