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
use Toolkit\Cli\Cli;
use Toolkit\Cli\Color\Alert;
use Toolkit\Cli\Color\Prompt;

/**
 * Class CliTest
 * @package Toolkit\CliTest
 */
class CliTest extends TestCase
{
    public function testAlert(): void
    {
        $str = Alert::global()->sprint('hello');

        self::assertNotEmpty($str);

        Cli::alert('an message');
        Cli::alert('an message', 'error');
    }

    public function testPrompt(): void
    {
        $str = Prompt::global()->sprint('hello');

        self::assertNotEmpty($str);

        Cli::prompt('an message');
    }
}
