<?php

namespace falkirks\minereset\command;

use falkirks\minereset\MineReset;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class MineCommand extends Command
{
    /** @var MineReset */
    protected MineReset $api;
    /** @var  SubCommand[] */
    protected array $subCommands;

    public function __construct(MineReset $api)
    {
        parent::__construct("mine", "Mine reset command", "/mine <create|set|list|reset|reset-all|destroy|report> <name> [parameters]");
        $this->setPermission("mine.command");
        $this->api = $api;
        $this->subCommands = [];
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param string[]      $args
     *
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->testPermission($sender)) {
            return;
        }
        if (count($args) > 0 && array_key_exists($args[0], $this->subCommands)) {
            $this->subCommands[array_shift($args)]->execute($sender, $commandLabel, $args);
            return;
        }

        $sender->sendMessage($this->getUsage());
    }

    public function registerSubCommand(string $name, SubCommand $command, $aliases = []): void
    {
        $this->subCommands[$name] = $command;

        foreach ($aliases as $alias) {
            if (!isset($this->subCommands[$alias])) {
                $this->registerSubCommand($alias, $command);
            }
        }
    }
}