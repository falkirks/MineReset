<?php

namespace falkirks\minereset\command;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AboutCommand extends SubCommand
{
	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if ($sender->hasPermission("minereset.command.about")) {
			$sender->sendMessage(TextFormat::GREEN . "MineReset 4.0.0-beta by Falk, updated by Octopush.");
			$sender->sendMessage(TextFormat::GREEN . "Github: https://github.com/octopussh");
		} else {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
		}
		return true;
	}
}