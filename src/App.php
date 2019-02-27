<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-15
 * Time: 10:51
 */

namespace Toolkit\Cli;

/**
 * Class App - A lite CLI Application
 * @package Inhere\Console
 */
class App
{
    /** @var string Current dir */
    private $pwd;

    /**
     * @var array Parsed from `arg0 name=val var2=val2`
     */
    private $args = [];

    /**
     * @var array Parsed from `--name=val --var2=val2 -d`
     */
    private $opts = [];

    /** @var string */
    private $script;

    /** @var string */
    private $command = '';

    /**
     * @var array User add commands
     */
    private $commands = [];

    /**
     * @var array Description message for the commands
     */
    private $messages = [];

    /**
     * @var int
     */
    private $keyWidth = 12;

    /**
     * Class constructor.
     * @param array|null $argv
     */
    public function __construct(array $argv = null)
    {
        // get current dir
        $this->pwd = \getcwd();

        // parse cli argv
        $argv = $argv ?? (array)$_SERVER['argv'];
        // get script file
        $this->script = \array_shift($argv);
        // parse flags
        [$this->args, $this->opts] = Flags::simpleParseArgv($argv);
    }

    /**
     * @param bool $exit
     * @throws \InvalidArgumentException
     */
    public function run(bool $exit = true): void
    {
        if (isset($this->args[0])) {
            $this->command = $this->args[0];
            unset($this->args[0]);
        }

        $this->dispatch($exit);
    }

    /**
     * @param bool $exit
     * @throws \InvalidArgumentException
     */
    public function dispatch(bool $exit = true): void
    {
        if (!$command = $this->command) {
            $this->displayHelp();
            return;
        }

        $status = 0;

        try {
            if (isset($this->commands[$command])) {
                $status = $this->runHandler($command, $this->commands[$command]);
            } else {
                $this->displayHelp("The command {$command} not exists!");
            }
        } catch (\Throwable $e) {
            $status = $this->handleException($e);
        }

        if ($exit) {
            $this->stop($status);
        }
    }

    /**
     * @param int $code
     */
    public function stop($code = 0): void
    {
        exit((int)$code);
    }

    /**
     * @param string $command
     * @param        $handler
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function runHandler(string $command, $handler)
    {
        if (\is_string($handler)) {
            // function name
            if (\function_exists($handler)) {
                return $handler($this);
            }

            if (\class_exists($handler)) {
                $handler = new $handler;

                // $handler->execute()
                if (\method_exists($handler, 'execute')) {
                    return $handler->execute($this);
                }
            }
        }

        // a \Closure OR $handler->__invoke()
        if (\method_exists($handler, '__invoke')) {
            return $handler($this);
        }

        throw new \InvalidArgumentException("Invalid handler of the command: $command");
    }

    /**
     * @param \Throwable $e
     * @return int
     */
    protected function handleException(\Throwable $e): int
    {
        $code = $e->getCode() !== 0 ? $e->getCode() : 133;

        printf(
            "Exception(%d): %s\nFile: %s(Line %d)\nTrace:\n%s\n",
            $code,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        return $code;
    }

    /**
     * @param string   $command
     * @param callable $handler
     * @param string   $description
     * @throws \InvalidArgumentException
     */
    public function addCommand(string $command, callable $handler, string $description = ''): void
    {
        if (!$command || !$handler) {
            throw new \InvalidArgumentException('Invalid arguments');
        }

        if (($len = \strlen($command)) > $this->keyWidth) {
            $this->keyWidth = $len;
        }

        $this->commands[$command] = $handler;
        $this->messages[$command] = \trim($description);
    }

    /**
     * @param array $commands
     * @throws \InvalidArgumentException
     */
    public function commands(array $commands): void
    {
        foreach ($commands as $command => $handler) {
            $des = '';

            if (\is_array($handler)) {
                $conf    = \array_values($handler);
                $handler = $conf[0];
                $des     = $conf[1] ?? '';
            }

            $this->addCommand($command, $handler, $des);
        }
    }

    /****************************************************************************
     * helper methods
     ****************************************************************************/

    /**
     * @param string $err
     */
    public function displayHelp(string $err = ''): void
    {
        if ($err) {
            echo Color::render("<red>ERROR</red>: $err\n\n");
        }

        $commandWidth = 12;
        // help
        $help = "Welcome to the Lite Console Application.\n\n<comment>Available Commands:</comment>\n";

        foreach ($this->messages as $command => $desc) {
            $command = \str_pad($command, $commandWidth, ' ');
            $desc    = $desc ?: 'No description for the command';
            $help    .= "  $command   $desc\n";
        }

        echo Color::render($help) . PHP_EOL;
        exit(0);
    }

    /**
     * @param string|int $name
     * @param mixed      $default
     * @return mixed|null
     */
    public function getArg($name, $default = null)
    {
        return $this->args[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed|null
     */
    public function getOpt(string $name, $default = null)
    {
        return $this->opts[$name] ?? $default;
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
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param string $script
     */
    public function setScript(string $script): void
    {
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param array $commands
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * @return int
     */
    public function getKeyWidth(): int
    {
        return $this->keyWidth;
    }

    /**
     * @param int $keyWidth
     */
    public function setKeyWidth(int $keyWidth): void
    {
        $this->keyWidth = $keyWidth > 1 ? $keyWidth : 12;
    }

    /**
     * @return string
     */
    public function getPwd(): string
    {
        return $this->pwd;
    }

}
