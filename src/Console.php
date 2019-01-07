<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/4
 * Time: 上午9:11
 */

namespace Toolkit\Cli;

/**
 * Class Console
 * @package Toolkit\Cli
 */
class Console extends Cli
{
    public const LOG_LEVEL2TAG = [
        'info'    => 'info',
        'warn'    => 'warning',
        'warning' => 'warning',
        'debug'   => 'cyan',
        'notice'  => 'notice',
        'error'   => 'error',
    ];

    /**
     * print log to console
     * @param string $msg
     * @param array  $data
     * @param string $type
     * @param array  $opts
     * [
     *  '_category' => 'application',
     *  'process' => 'work',
     *  'pid' => 234,
     *  'coId' => 12,
     * ]
     */
    public static function log(string $msg, array $data = [], string $type = 'info', array $opts = [])
    {
        if (isset(self::LOG_LEVEL2TAG[$type])) {
            $type = ColorTag::add(\strtoupper($type), self::LOG_LEVEL2TAG[$type]);
        }

        $userOpts = [];

        foreach ($opts as $n => $v) {
            if (\is_numeric($n) || \strpos($n, '_') === 0) {
                $userOpts[] = "[$v]";
            } else {
                $userOpts[] = "[$n:$v]";
            }
        }

        $optString = $userOpts ? ' ' . \implode(' ', $userOpts) : '';

        Cli::write(\sprintf(
            '%s [%s]%s %s %s',
            \date('Y/m/d H:i:s'),
            $type,
            $optString,
            \trim($msg),
            $data ? \PHP_EOL . \json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT) : ''
        ));
    }
}
