<?php

namespace falkirks\minereset\command;


use falkirks\minereset\listener\MineCreationSession;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CreateCommand extends SubCommand
{

	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if (!$sender->hasPermission("minereset.command.create")) {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
			return true;
		}

		if (!($sender instanceof Player)) {
			$sender->sendMessage(TextFormat::RED . "This command can only be run in-game." . TextFormat::RESET);
			return true;
		}

		if (!isset($args[0])) {
			$sender->sendMessage("Usage: /mine create <name>");
			return true;
		}

		if ($this->getApi()->getCreationListener()->playerHasSession($sender)) {
			$sender->sendMessage("Hold up! You are already in the process of creating a mine. You need to finish that first.");
			return true;
		}

		if (isset($this->getApi()->getMineManager()[$args[0]])) {
			$sender->sendMessage("That mine already exists. You must run \"/mine destroy $args[0]\" before creating a new one.");
			return true;
		}

		$this->getApi()->getCreationListener()->addSession(new MineCreationSession($args[0], $sender));
		$sender->sendMessage("Tap a block to set position A.");
		return true;
	}
}