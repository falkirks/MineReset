<?php

namespace falkirks\minereset\command;


use falkirks\minereset\MineReset;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

class MineCommand extends Command implements PluginOwned
{
	/** @var MineReset */
	protected MineReset $api;
	/** @var  SubCommand[] */
	protected array $subCommands;

	public function __construct(MineReset $api) {
		parent::__construct("mine", "Mine reset command", "/mine <create|set|list|reset|reset-all|destroy|report> <name> [parameters]");
		$this->setPermission("minereset.command");
		$this->api = $api;
		$this->subCommands = [];
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if (!$this->testPermission($sender)) {
			return false;
		}
		if (count($args) > 0 && array_key_exists($args[0], $this->subCommands)) {
			return $this->subCommands[array_shift($args)]->execute($sender, $commandLabel, $args);
		} else {
			$sender->sendMessage($this->getUsage());
			return false;
		}
	}

	public function registerSubCommand(string $name, SubCommand $command, array $aliases = []): void {
		$this->subCommands[$name] = $command;

		foreach ($aliases as $alias) {
			if (!isset($this->subCommands[$alias])) {
				$this->registerSubCommand($alias, $command);
			}
		}
	}

	public function getOwningPlugin(): Plugin {
		return $this->api;
	}
}
