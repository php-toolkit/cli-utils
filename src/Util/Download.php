<?php declare(strict_types=1);
/**
 * This file is part of toolkit/cli-utils.
 *
 * @link     https://github.com/php-toolkit/cli-utils
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace Toolkit\Cli\Util;

use RuntimeException;
use Toolkit\Cli\Cli;
use function array_merge;
use function basename;
use function error_get_last;
use function fclose;
use function file_put_contents;
use function fopen;
use function getcwd;
use function getenv;
use function is_resource;
use function preg_replace;
use function printf;
use function round;
use function str_repeat;
use function stream_context_create;
use function stream_context_set_params;
use function strpos;
use function trim;
use const STREAM_NOTIFY_AUTH_REQUIRED;
use const STREAM_NOTIFY_AUTH_RESULT;
use const STREAM_NOTIFY_COMPLETED;
use const STREAM_NOTIFY_CONNECT;
use const STREAM_NOTIFY_FAILURE;
use const STREAM_NOTIFY_FILE_SIZE_IS;
use const STREAM_NOTIFY_MIME_TYPE_IS;
use const STREAM_NOTIFY_PROGRESS;
use const STREAM_NOTIFY_REDIRECTED;
use const STREAM_NOTIFY_RESOLVE;

/**
 * Class Download
 *
 * @package Toolkit\Cli
 */
final class Download
{
    public const PROGRESS_TEXT = 'text';

    public const PROGRESS_BAR = 'bar';

    /** @var string */
    private $url;

    /** @var string */
    private $saveAs;

    /** @var int */
    private $fileSize;

    /** @var string */
    private $showType;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * http context options
     *
     * @var array
     * @link https://www.php.net/manual/en/context.http.php
     */
    private $httpCtxOptions = [];

    /**
     * @param string $url
     * @param string $saveAs
     * @param string $type
     *
     * @return Download
     */
    public static function create(string $url, string $saveAs = '', string $type = self::PROGRESS_TEXT): Download
    {
        return new self($url, $saveAs, $type);
    }

    /**
     * eg: php down.php <http://example.com/file> <localFile>
     *
     * @param string $url
     * @param string $saveAs
     * @param string $type
     *
     * @return Download
     * @throws RuntimeException
     */
    public static function file(string $url, string $saveAs = '', string $type = self::PROGRESS_TEXT): Download
    {
        $d = new self($url, $saveAs, $type);

        return $d->start();
    }

    /**
     * Download constructor.
     *
     * @param string $url
     * @param string $saveAs
     * @param string $type
     */
    public function __construct(string $url, string $saveAs = '', string $type = self::PROGRESS_TEXT)
    {
        $this->setUrl($url);
        $this->setSaveAs($saveAs);
        $this->setShowType($type);
    }

    /**
     * start download
     *
     * @return $this
     * @throws RuntimeException
     */
    public function start(): self
    {
        if (!$this->url) {
            throw new RuntimeException("Please the property 'url' and 'saveAs'.", -1);
        }

        // default save to current dir.
        if (!$save = $this->saveAs) {
            $save = getcwd() . '/' . basename($this->url);
            // reset
            $this->saveAs = $save;
        }

        $ctx = $this->createStreamContext();

        // register stream notification callback
        // https://www.php.net/manual/en/function.stream-notification-callback.php
        stream_context_set_params($ctx, [
            'notification' => [$this, 'progressShow']
        ]);

        Cli::write("Download: {$this->url}\n Save As: {$save}\n");

        $fp = fopen($this->url, 'rb', false, $ctx);

        if (is_resource($fp) && file_put_contents($save, $fp)) {
            Cli::write("\nDone!");
        } else {
            $err = error_get_last();
            Cli::stderr("\nErr.rrr..orr...\n {$err['message']}\n", true, -1);
        }

        // close resource
        if (is_resource($fp)) {
            fclose($fp);
        }

        $this->fileSize = null;
        return $this;
    }

    protected function createStreamContext()
    {
        // https://www.php.net/manual/en/context.http.php
        $httpOpts = [
            'max_redirects'    => '15',
            'protocol_version' => '1.1',
            'header'           => [
                'Connection: close', // on 'protocol_version' => '1.1'
            ],
            // 'follow_location' => '1',
            // 'timeout'         => 0,
            // 'proxy'           => 'tcp://my-proxy.localhost:3128',
        ];

        if ($this->httpCtxOptions) {
            $httpOpts = array_merge($httpOpts, $this->httpCtxOptions);
        }

        $isHttps = strpos($this->url, 'https') === 0;

        if (!isset($httpOpts['proxy'])) {
            if ($isHttps) {
                $proxyUrl = (string)getenv('https_proxy');
            } else {
                $proxyUrl = (string)getenv('http_proxy');
            }

            if ($proxyUrl) {
                $this->debugf('Uses proxy ENV variable: http%s_proxy=%s', $isHttps ? 's' : '', $proxyUrl);

                // convert 'http://127.0.0.1:10801' to 'tcp://127.0.0.1:10801'
                // see https://github.com/guzzle/guzzle/issues/1555#issuecomment-239450114
                if (strpos($proxyUrl, 'http') === 0) {
                    $proxyUrl = preg_replace('/^http[s]?/', 'tcp', $proxyUrl);
                }

                $httpOpts['proxy'] = $proxyUrl;
                // see https://www.php.net/manual/en/context.http.php#110449
                $httpOpts['request_fulluri'] = true;
            }
        }

        return stream_context_create([
            'http' => $httpOpts,
        ]);
    }

    /**
     * @link https://www.php.net/manual/en/function.stream-notification-callback.php
     *
     * @param int         $notifyCode       stream notify code
     * @param int         $severity         severity code
     * @param string|null $message          Message text
     * @param int         $messageCode      Message code
     * @param int         $transferredBytes Have been transferred bytes
     * @param int         $maxBytes         Target max length bytes
     */
    public function progressShow(
        int $notifyCode,
        int $severity,
        ?string $message,
        $messageCode,
        int $transferredBytes,
        int $maxBytes
    ): void {
        $msg = '';

        switch ($notifyCode) {
            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                $msg = "NOTIFY: $message(NO: $messageCode, Severity: $severity)";
                /* Ignore */
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $msg = "Being redirected to: $message";
                break;

            case STREAM_NOTIFY_CONNECT:
                $msg = '> Connected ...';
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $this->fileSize = $maxBytes;
                // print size
                $size = sprintf('%2d', $maxBytes / 1024);
                $msg  = "Got the file size: <info>$size</info> kb";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $msg = "Found the mime-type: <info>$message</info>";
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($transferredBytes > 0) {
                    $this->showProgressByType($transferredBytes);
                }
                break;
        }

        $msg && Cli::write($msg);
    }

    /**
     * @param $transferredBytes
     */
    public function showProgressByType($transferredBytes): void
    {
        if ($transferredBytes <= 0) {
            return;
        }

        $tfKbSize = $transferredBytes / 1024;
        if ($this->fileSize === null) {
            printf("\r\rUnknown file size... %2d kb done..", $tfKbSize);
            return;
        }

        $totalSize = $this->fileSize / 1024;

        $percent = $transferredBytes / $this->fileSize;
        if ($this->showType === self::PROGRESS_BAR) {
            $barWidth  = 60;
            $boxNumber = (int)round($percent * $barWidth); // ■ =
            if ($barWidth === $boxNumber) {
                $completed = 100;
                $tfKbSize  = $totalSize;
            } else {
                $completed = (int)round($percent * 100);
            }

            $paddingBar = str_repeat('=', $boxNumber) . '>';
            printf("\r\r[%-60s] %d%% (%2d/%2d kb)", $paddingBar, $completed, $tfKbSize, $totalSize);
        } else {
            if ((int)round($percent * 100) === 100) {
                $tfKbSize = $totalSize;
            }

            //$msg = "Made some progress, downloaded <info>$transferredBytes</info> so far";
            printf("\r\rMade some progress, downloaded %2d kb so far", $tfKbSize);
        }
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function debugf(string $format, ...$args): void
    {
        if ($this->debug) {
            Cli::printf("[DEBUG] $format\n", ...$args);
        }
    }

    /**
     * @return string
     */
    public function getShowType(): string
    {
        return $this->showType;
    }

    /**
     * @param string $showType
     */
    public function setShowType(string $showType): void
    {
        $this->showType = $showType === self::PROGRESS_BAR ? self::PROGRESS_BAR : self::PROGRESS_TEXT;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = trim($url);
    }

    /**
     * @return string
     */
    public function getSaveAs(): string
    {
        return $this->saveAs;
    }

    /**
     * @param string $saveAs
     */
    public function setSaveAs(string $saveAs): void
    {
        if ($saveAs) {
            $this->saveAs = trim($saveAs);
        }
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @param array $httpCtxOptions
     */
    public function setHttpCtxOptions(array $httpCtxOptions): void
    {
        $this->httpCtxOptions = $httpCtxOptions;
    }
}
