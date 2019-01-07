<?php
/**
 * demo for cli download file.
 */

use Toolkit\Cli\Download;

require dirname(__DIR__) . '/test/boot.php';

$url  = 'http://no2.php.net/distributions/php-7.2.5.tar.bz2';
$app  = new \Toolkit\Cli\App();
$down = Download::file($url, '');

$type = $app->getArg(0, 'text');
if ($type === 'bar') {
    $down->setShowType($type);
}

$down->start();
