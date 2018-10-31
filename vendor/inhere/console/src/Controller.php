<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Base\AbstractCommand;
use Inhere\Console\Base\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Utils\FormatUtil;
use Inhere\Console\Utils\Helper;
use Inhere\Console\Utils\Annotation;

/**
 * Class Controller
 * @package Inhere\Console
 */
abstract class Controller extends AbstractCommand implements ControllerInterface
{
    /** @var array */
    private static $commandAliases;

    /** @var array */
    protected static $globalOptions = [
        '--show-disabled' => 'Whether display disabled commands',
    ];

    /** @var string Action name, no suffix. */
    private $action;

    /** @var string */
    private $delimiter = ':'; // '/' ':'

    /** @var bool Execution alone */
    private $executionAlone = false;

    /** @var string */
    private $defaultAction = 'help';

    /** @var string */
    private $actionSuffix = 'Command';

    /** @var string */
    protected $notFoundCallback = 'notFound';

    /**
     * @var array From disabledCommands()
     */
    protected $disabledCommands = [];

    /**
     * define command alias map
     * @return array
     */
    protected static function commandAliases(): array
    {
        return [
            // alias => command
            // 'i'  => 'install',
        ];
    }

    protected function init()
    {
        $list = $this->disabledCommands();
        $this->disabledCommands = $list ? array_flip($list) : [];

        if (!$this->actionSuffix) {
            $this->actionSuffix = 'Command';
        }
    }

    /**
     * define disabled command list.
     * @return array
     */
    protected function disabledCommands(): array
    {
        return [
            // 'command1', 'command2'
        ];
    }

    /**
     * @param string $command
     * @return int
     * @throws \ReflectionException
     */
    public function run(string $command = ''): int
    {
        $this->action = $this->getRealCommandName(trim($command, $this->delimiter));

        if (!$this->action) {
            return $this->showHelp();
        }

        return parent::run($command);
    }

    /**
     * load command configure
     */
    final protected function configure()
    {
        if ($action = $this->action) {
            $method = $action . 'Configure';

            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * 运行控制器的 action
     * @param  Input $input
     * @param  Output $output
     * @return mixed
     * @throws \ReflectionException
     */
    protected function execute($input, $output)
    {
        $action = FormatUtil::camelCase(trim($this->action ?: $this->defaultAction, $this->delimiter));

        if ($this->isDisabled($action)) {
            $output->liteError(sprintf(
                "Sorry, The command '$action' is invalid in the group '%s'!",
                static::getName()
            ));

            return -1;
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (\method_exists($this, $method) && (($rfm = new \ReflectionMethod($this, $method)) && $rfm->isPublic())) {
            // before
            if (\method_exists($this, $before = 'before' . ucfirst($action))) {
                $this->$before($input, $output);
            }

            // run action
            $status = $this->$method($input, $output);

            // after
            if (\method_exists($this, $after = 'after' . ucfirst($action))) {
                $this->$after($input, $output);
            }

            return (int)$status;
        }

        // if you defined the method '$this->notFoundCallback' , will call it
        if (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $status = $this->{$notFoundCallback}($action);
        } else {
            $group = static::getName();
            $status = -1;
            $output->liteError("Sorry, The command '$action' not exist of the group '{$group}'!");

            // find similar command names
            $similar = Helper::findSimilar($action, $this->getAllCommandMethods(null, true));

            if ($similar) {
                $output->write(sprintf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar)));
            } else {
                $this->showCommandList();
            }
        }

        return $status;
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    protected function showHelp(): bool
    {
        if (true === parent::showHelp()) {
            return true;
        }

        return $this->helpCommand();
    }

    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}:[command] -h</info> OR <info>{command} [command]</info> OR <info>{name} [command] -h</info>
     * @options
     *  -s, --search  Search command by input keywords
     *  --format      Set the help information dump format(raw, xml, json, markdown)
     * @example
     *  {script} {name} -h
     *  {script} {name}:help
     *  {script} {name}:help index
     *  {script} {name}:index -h
     *  {script} {name} index
     *
     * @return int
     * @throws \ReflectionException
     */
    final public function helpCommand(): int
    {
        $action = $this->action;

        // show all commands of the controller
        if (!$action && !($action = $this->input->getFirstArg())) {
            $this->showCommandList();
            return 0;
        }

        $action = FormatUtil::camelCase($action);
        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;
        $aliases = self::getCommandAliases($action);

        // show help info for a command.
        return $this->showHelpByMethodAnnotations($method, $action, $aliases);
    }

    protected function beforeShowCommandList()
    {
        // do something ...
    }

    /**
     * show command list of the controller class
     * @throws \ReflectionException
     */
    final public function showCommandList()
    {
        $this->beforeShowCommandList();

        $ref = new \ReflectionClass($this);
        $sName = \lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = Annotation::description($ref->getDocComment()) ?: 'No description for the console controller';
        }

        $commands = [];
        $showDisabled = (bool)$this->getOpt('show-disabled', false);
        $defaultDes = 'No description message';

        foreach ($this->getAllCommandMethods($ref) as $cmd => $m) {
            if (!$cmd) {
                continue;
            }

            $desc = Annotation::firstLine($m->getDocComment()) ?: $defaultDes;

            // is a annotation tag
            if ($desc[0] === '@') {
                $desc = $defaultDes;
            }

            if ($this->isDisabled($cmd)) {
                if (!$showDisabled) {
                    continue;
                }

                $desc .= '[<red>DISABLED</red>]';
            }

            $aliases = self::getCommandAliases($cmd);
            $desc .= $aliases ? Helper::wrapTag(' [alias: ' . implode(',', $aliases) . ']', 'info') : '';
            $commands[$cmd] = $desc;
        }

        // sort commands
        \ksort($commands);

        // move 'help' to last.
        if ($helpCmd = $commands['help'] ?? null) {
            unset($commands['help']);
            $commands['help'] = $helpCmd;
        }

        $script = $this->getScriptName();

        if ($this->executionAlone) {
            $name = $sName . ' ';
            $usage = "$script <info>{command}</info> [--options ...] [arguments ...]";
        } else {
            $name = $sName . $this->delimiter;
            $usage = "$script {$name}<info>{command}</info> [--options ...] [arguments ...]";
        }

        $this->output->startBuffer();
        $this->output->write(ucfirst($classDes) . PHP_EOL);
        $this->output->mList([
            'Usage:' => $usage,
            //'Group Name:' => "<info>$sName</info>",
            'Global Options:' => FormatUtil::alignmentOptions(array_merge(Application::getInternalOptions(), static::$globalOptions)),
            'Available Commands:' => $commands,
        ], [
            'sepChar' => '  ',
        ]);

        $this->write(sprintf(
            "More information about a command, please use: <cyan>$script $name{command} -h</cyan>",
            $this->executionAlone ? ' ' . $name : ''
        ));
        $this->output->flush();
    }

    /**
     * @param \ReflectionClass|null $ref
     * @param bool $onlyName
     * @return \Generator
     */
    protected function getAllCommandMethods(\ReflectionClass $ref = null, $onlyName = false)
    {
        $ref = $ref ?: new \ReflectionObject($this);

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);

        foreach ($ref->getMethods() as $m) {
            $mName = $m->getName();

            if ($m->isPublic() && substr($mName, -$suffixLen) === $suffix) {
                // suffix is empty ?
                $cmd = $suffix ? substr($mName, 0, -$suffixLen) : $mName;

                if ($onlyName) {
                    yield $cmd;
                } else {
                    yield $cmd => $m;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return mixed|string
     */
    protected function getRealCommandName(string $name)
    {
        if (!$name) {
            return $name;
        }

        $map = self::getCommandAliases();

        return $map[$name] ?? $name;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isDisabled(string $name): bool
    {
        return isset($this->disabledCommands[$name]);
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

    /**
     * @return array
     */
    public function getDisabledCommands(): array
    {
        return $this->disabledCommands;
    }

    /**
     * @param string|null $name
     * @return array
     */
    public static function getCommandAliases(string $name = null): array
    {
        if (null === self::$commandAliases) {
            self::$commandAliases = static::commandAliases();
        }

        if ($name) {
            return self::$commandAliases ? array_keys(self::$commandAliases, $name, true) : [];
        }

        return self::$commandAliases;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction(string $action): self
    {
        if ($action) {
            $this->action = FormatUtil::camelCase($action);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    /**
     * @param string $defaultAction
     */
    public function setDefaultAction(string $defaultAction)
    {
        $this->defaultAction = $defaultAction;
    }

    /**
     * @return string
     */
    public function getActionSuffix(): string
    {
        return $this->actionSuffix;
    }

    /**
     * @param string $actionSuffix
     */
    public function setActionSuffix(string $actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;
    }

    /**
     * @return string|null
     */
    public function getNotFoundCallback()
    {
        return $this->notFoundCallback;
    }

    /**
     * @param string $notFoundCallback
     */
    public function setNotFoundCallback(string $notFoundCallback)
    {
        $this->notFoundCallback = $notFoundCallback;
    }

    /**
     * @return bool
     */
    public function isExecutionAlone(): bool
    {
        return $this->executionAlone;
    }

    /**
     * @param bool $executionAlone
     */
    public function setExecutionAlone($executionAlone = true)
    {
        $this->executionAlone = (bool)$executionAlone;
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }
}
