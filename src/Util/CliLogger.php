<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use InvalidArgumentException;
use Stringable;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Color\ColorTag;
use function date;
use function fopen;
use function implode;
use function is_numeric;
use function is_resource;
use function is_string;
use function json_encode;
use function sprintf;
use function strtoupper;
use function trim;
use const STDOUT;

/**
 * class CliLogger
 *
 * @author inhere
 */
class CliLogger // implements LoggerInterface
{
    public const EMERGENCY = 'emergency';

    public const ALERT     = 'alert';

    public const CRITICAL  = 'critical';

    public const ERROR     = 'error';

    public const WARNING   = 'warning';

    public const NOTICE    = 'notice';

    public const INFO      = 'info';

    public const DEBUG     = 'debug';

    public const LEVEL2COLOR = [
        'info'      => 'info',
        'warn'      => 'warning',
        'warning'   => 'warning',
        'debug'     => 'cyan',
        'notice'    => 'notice',
        'error'     => 'error',
        'alert'     => 'red',
        'critical'  => 'red1',
        'emergency' => 'error',
    ];

    /**
     * @var resource
     */
    protected $output;

    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string|Stringable $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $labelStr = '';
        if (isset($context['_labels'])) {
            $userLabels = [];
            foreach ($context['_labels'] as $n => $v) {
                if (is_numeric($n) || str_starts_with($n, '_')) {
                    $userLabels[] = "[$v]";
                } else {
                    $userLabels[] = "[$n:$v]";
                }
            }
            unset($context['_labels']);

            $labelStr = $userLabels ? ' ' . implode(' ', $userLabels) : '';
        }

        // add color.
        if (isset(self::LEVEL2COLOR[$level])) {
            $level = ColorTag::add(strtoupper($level), self::LEVEL2COLOR[$level]);
        } else {
            $level = strtoupper($level);
        }

        $json = $context ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $str  = sprintf("%s [%s]%s %s %s\n", date('Y/m/d H:i:s'), $level, $labelStr, trim($message), $json);

        Cli::write($str, false, false, [
            'stream' => $this->getOutput(),
        ]);
        // fwrite($this->getOutput(), $str);
    }

    /**
     * @return resource
     */
    public function getOutput()
    {
        if ($this->output === null) {
            $this->output = STDOUT;
        }

        return $this->output;
    }

    /**
     * @param mixed $output an resource.
     */
    public function setOutput(mixed $output): void
    {
        if (is_string($output)) {
            $output = fopen($output, 'wb');
        }

        if (!is_resource($output)) {
            throw new InvalidArgumentException('Excepted an resource');
        }

        $this->output = $output;
    }
}
