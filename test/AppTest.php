<?php declare(strict_types=1);


namespace Toolkit\CliTest;

use PHPUnit\Framework\TestCase;
use Toolkit\Cli\App;

/**
 * Class AppTest
 * @package Toolkit\CliTest
 */
class AppTest extends TestCase
{
    public function testBasic(): void
    {
        $conf = [
            'desc' => 'test cli application',
        ];

        $app = new App($conf);

        self::assertSame($conf['desc'], $app->getParam('desc'));

        $app->addCommands([
            [
                'name' => 'test1',
                'handler' => function (App $app) {
                    $app->setParam('test1', 'test1 running');
                },
            ]
        ]);
    }
}