<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use BadMethodCallException;
use Stringable;
use function method_exists;

/**
 * class Clog
 *
 * @author inhere
 * @method static alert(Stringable|string $message, array $context = []): void
 * @method static emerg(Stringable|string $message, array $context = []): void
 * @method static emergency(Stringable|string $message, array $context = []): void
 * @method static critical(Stringable|string $message, array $context = []): void
 * @method static warn(Stringable|string $message, array $context = []): void
 * @method static warning(Stringable|string $message, array $context = []): void
 * @method static notice(Stringable|string $message, array $context = []): void
 * @method static info(Stringable|string $message, array $context = []): void
 * @method static debug(Stringable|string $message, array $context = []): void
 * @method static error(Stringable|string $message, array $context = []): void
 */
class Clog
{
    public const ALIAS = [
        'warn'  => 'warning',
        'emerg' => 'emergency',
    ];

    public const LEVEL_NAMES = [
        'emerg',
        // 'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    /**
     * @var CliLogger|null
     */
    private static ?CliLogger $std = null;

    /**
     * @return CliLogger
     */
    public static function std(): CliLogger
    {
        if (self::$std === null) {
            self::$std = new CliLogger();
        }

        return self::$std;
    }

    /**
     * @param string $level
     * @param Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public static function log(string $level, Stringable|string $message, array $context = []): void
    {
        $level = self::ALIAS[$level] ?? $level;

        self::std()->log($level, $message, $context);
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return void
     */
    public static function __callStatic(string $method, array $args): void
    {
        $method = self::ALIAS[$method] ?? $method;

        /** @see CliLogger::log() */
        if (method_exists(self::std(), $method)) {
            self::std()->$method(...$args);
            return;
        }

        throw new BadMethodCallException("call invalid log method: $method");
    }

    /**
     * @return string[]
     */
    public static function getLevelNames(): array
    {
        return self::LEVEL_NAMES;
    }
}
