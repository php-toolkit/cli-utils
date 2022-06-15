<?php declare(strict_types=1);

namespace Toolkit\Cli;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Toolkit\Cli\Helper\FlagHelper;
use function array_shift;
use function basename;
use function class_exists;
use function count;
use function function_exists;
use function implode;
use function max;
use function method_exists;
use function str_contains;
use function str_pad;
use function strlen;
use function strtr;
use function ucfirst;

/**
 * class LiteCmd
 *
 * @author inhere
 * @date 2022/6/15
 */
class CliApp
{
    private string $pwd;

    private int $optWidth = 14;
    private int $argWidth = 14;

    /**
     * @var string
     */
    public string $name;

    public string $desc;

    public string $usage = '';

    public string $help = '';

    private string $binFile = '';

    /**
     * @var array
     */
    private array $flags = [];

    /**
     * @var array
     */
    public array $opts = [];

    /**
     * @var array
     */
    public array $args = [];

    /**
     * @var array
     */
    private array $remainArgs = [];

    /**
     * @var callable
     */
    private $handler;

    /**
     * @param string $name
     * @param string $desc
     *
     * @return CliApp
     */
    public static function new(string $name, string $desc = ''): self
    {
        return new self($name, $desc);
    }

    /**
     * Class constructor.
     *
     * @param string $name
     * @param string $desc
     */
    public function __construct(string $name, string $desc = '')
    {
        $this->name = $name;
        $this->desc = $desc;

        // get current dir
        $this->pwd = (string)getcwd();

        $this->addOpt('help', 'h', 'display help information', false, ['type' => 'bool']);
    }

    /**
     * @param Closure(CliApp):void $fn
     *
     * @return CliApp
     */
    public function config(Closure $fn): self
    {
        $fn($this);
        return $this;
    }

    /**
     * @var array{string: array{name:string, short:string, desc: string, default: mixed, required:bool}}
     */
    protected array $bindOpts = [];

    /**
     * @var array{string: string}
     */
    protected array $optShorts = [];

    /**
     * @param string $name
     * @param string $short
     * @param string $desc
     * @param mixed|null $default
     * @param array{type: string, required:bool} $moreSet
     *
     * @return $this
     */
    public function addOpt(string $name, string $short, string $desc, mixed $default = null, array $moreSet = []): self
    {
        $this->checkOptName($name);

        if ($short) {
            $this->checkOptName($short, true);
            $this->optShorts[$short] = $name;
        }

        $this->optWidth = (int)max(strlen($name . $short) + 6, $this->optWidth);

        // add
        $this->bindOpts[$name] = [
            'name'     => $name,
            'short'    => $short,
            'desc'     => $desc ?: 'No description for the option',
            'type'     => $moreSet['type'] ?? 'string',
            'default'  => $default,
            'required' => (bool)($moreSet['required'] ?? false),
        ];

        if ($default !== null) {
            $this->opts[$name] = $default;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param bool $isShort
     *
     * @return void
     */
    private function checkOptName(string $name, bool $isShort = false): void
    {
        if (isset($this->bindOpts[$name])) {
            $errMsg = $isShort ? "short '$name' has been use as a option name" : "option '$name' has been registered";
            throw new InvalidArgumentException($errMsg);
        }

        if (isset($this->optShorts[$name])) {
            $usedName = $this->optShorts[$name];
            $errMsg   = $isShort ? "short '$name' has been used on option: $usedName" : "name '$name' has been use as a short name";
            throw new InvalidArgumentException($errMsg);
        }
    }

    /**
     * @var array{string: array{name:string, short:string, desc: string, default: mixed, required:bool}}
     */
    protected array $bindArgs = [];

    /**
     * @param string $name
     * @param string $desc
     * @param mixed|null $default
     * @param array{type: string, required:bool} $moreSet
     *
     * @return $this
     */
    public function addArg(string $name, string $desc, mixed $default = null, array $moreSet = []): self
    {
        $index = count($this->bindArgs);

        // add
        $this->bindArgs[$name] = [
            'name'     => $name,
            'index'    => $index,
            'desc'     => $desc ?: 'No description for the argument',
            'type'     => $moreSet['type'] ?? 'string',
            'default'  => $default,
            'required' => (bool)($moreSet['required'] ?? false),
        ];

        if ($default !== null) {
            $this->args[$name] = $default;
        }
        return $this;
    }

    /**
     * @param array|null $flags
     *
     * @return int
     */
    public function run(array $flags = null): int
    {
        if ($flags === null) {
            $flags = $_SERVER['argv'];
        }

        $this->binFile = array_shift($flags);
        $this->flags   = $flags;

        // parse flags
        [$args, $opts] = FlagHelper::parseArgv($flags, [
            'mergeOpts' => true,
            'boolOpts'  => ['h', 'help']
        ]);

        if (isset($opts['h']) || isset($opts['help'])) {
            $this->displayHelp();
            return 0;
        }

        try {
            $this->bindParsed($args, $opts);

            $status = $this->runHandler();
        } catch (Throwable $e) {
            $status = $this->handleException($e);
        }

        return (int)$status;
    }

    /**
     * @param array $args
     * @param array $opts
     *
     * @return void
     */
    private function bindParsed(array $args, array $opts): void
    {
        foreach ($this->bindOpts as $name => $bindOpt) {
            $short = $bindOpt['short'];
            if (isset($opts[$name])) {
                $this->opts[$name] = $opts[$name];
            } elseif ($short && isset($opts[$short])) {
                $this->opts[$name] = $opts[$short];
            }
        }

        foreach ($this->bindArgs as $name => $bindArg) {
            $index = $bindArg['index'];
            if (isset($args[$index])) {
                $this->args[$name] = $args[$index];
                unset($args[$index]);
            }
        }

        $this->remainArgs = $args;
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function runHandler(): mixed
    {
        $handler = $this->handler;

        if (is_string($handler)) {
            if (function_exists($handler)) {
                return $handler($this);
            }

            if (class_exists($handler)) {
                $handler = new $handler;

                // call $handler->execute()
                if (method_exists($handler, 'execute')) {
                    return $handler->execute($this);
                }
            }
        }

        // a \Closure OR $handler->__invoke()
        if (is_object($handler) && method_exists($handler, '__invoke')) {
            return $handler($this);
        }

        throw new RuntimeException("invalid handler of the command");
    }

    /**
     * @param Throwable $e
     *
     * @return int
     */
    protected function handleException(Throwable $e): int
    {
        if ($e instanceof InvalidArgumentException) {
            Color::println('ERROR: ' . $e->getMessage(), 'error');
            return 0;
        }

        $code = $e->getCode() !== 0 ? $e->getCode() : -1;
        $eTpl = "Exception(%d): %s\nFile: %s(Line %d)\nTrace:\n%s\n";

        // print exception message
        printf($eTpl, $code, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        return $code;
    }

    public function displayHelp(): void
    {
        $fullCmd = $this->binFile;

        $usage = $this->usage ?: "$fullCmd [--opts ...] [args ...]";
        $desc  = $this->desc ?: 'No description for the command';

        $nodes = [
            ucfirst($desc),
            "<comment>Usage:</comment> \n  $usage\n",
        ];

        if ($bindOpts = $this->bindOpts) {
            ksort($bindOpts);

            $nodes[] = "<comment>Options:</comment>";
            foreach ($bindOpts as $name => $opt) {
                $names = [$name];
                if ($opt['short']) {
                    $names[] = $opt['short'];
                }

                $fullOpt = FlagHelper::buildOptHelpName($names);
                $fullOpt = str_pad($fullOpt, $this->optWidth);

                $optDesc = ucfirst($opt['desc']);
                $nodes[] = "  <green>$fullOpt</green>   $optDesc";
            }

            $nodes[] = '';
        }

        if ($this->bindArgs) {
            $nodes[] = "<comment>Arguments:</comment>";
            foreach ($this->bindArgs as $name => $arg) {
                $fullArg = str_pad($name, $this->argWidth);
                $optDesc = ucfirst($arg['desc']);
                $nodes[] = "  <green>$fullArg</green>   $optDesc";
            }
        }

        // user help
        if ($this->help) {
            $nodes[] = $this->help;
        }

        $help = implode("\n", $nodes);

        if (str_contains($help, '{{')) {
            $help = strtr($help, [
                '{{command}}' => basename($this->binFile),
                '{{fullCmd}}' => $fullCmd,
                '{{workDir}}' => $this->pwd,
                '{{pwdDir}}'  => $this->pwd,
                '{{script}}'  => $this->binFile,
                '{{binFile}}' => $this->binFile,
            ]);
        }

        Cli::println($help);
    }

    /**
     * @param int|string $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getArg(int|string $name, mixed $default = null): mixed
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * @param int|string $name
     * @param int $default
     *
     * @return int
     */
    public function getIntArg(int|string $name, int $default = 0): int
    {
        return (int)$this->getArg($name, $default);
    }

    /**
     * @param int|string $name
     * @param string $default
     *
     * @return string
     */
    public function getStrArg(int|string $name, string $default = ''): string
    {
        return (string)$this->getArg($name, $default);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getOpt(string $name, mixed $default = null): mixed
    {
        return $this->opts[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param int $default
     *
     * @return int
     */
    public function getIntOpt(string $name, int $default = 0): int
    {
        return (int)$this->getOpt($name, $default);
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getStrOpt(string $name, string $default = ''): string
    {
        return (string)$this->getOpt($name, $default);
    }

    /**
     * @param string $name
     * @param bool $default
     *
     * @return bool
     */
    public function getBoolOpt(string $name, bool $default = false): bool
    {
        return (bool)$this->getOpt($name, $default);
    }

    /****************************************************************************
     * getter/setter methods
     ****************************************************************************/

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return $this->opts;
    }

    /**
     * @param array $opts
     */
    public function setOpts(array $opts): void
    {
        $this->opts = $opts;
    }

    /**
     * @param string $name
     *
     * @return CliApp
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $desc
     *
     * @return CliApp
     */
    public function setDesc(string $desc): self
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @return string
     */
    public function getBinFile(): string
    {
        return $this->binFile;
    }

    /**
     * @return string
     */
    public function getBinName(): string
    {
        return basename($this->binFile);
    }

    /**
     * @param callable(CliApp):int $handler
     *
     * @return CliApp
     */
    public function setHandler(callable $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return string
     */
    public function getPwd(): string
    {
        return $this->pwd;
    }

    /**
     * @param string $help
     *
     * @return CliApp
     */
    public function setHelp(string $help): self
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @param string $usage
     *
     * @return CliApp
     */
    public function setUsage(string $usage): self
    {
        $this->usage = $usage;
        return $this;
    }

    /**
     * @return array
     */
    public function getBindOpts(): array
    {
        return $this->bindOpts;
    }

    /**
     * @return array
     */
    public function getRemainArgs(): array
    {
        return $this->remainArgs;
    }

}
