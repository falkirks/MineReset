<?php

namespace falkirks\minereset\command;


use falkirks\minereset\exception\MineResetException;
use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ResetAllCommand extends SubCommand
{
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if ($sender->hasPermission("minereset.command.resetall")) {
			$success = 0;
			foreach ($this->getApi()->getMineManager() as $mine) {
				if ($mine instanceof Mine) {
					try {
						$mine->reset();
						$success++;
						$this->getApi()->getResetProgressManager()->addObserver($mine->getName(), $sender);
					} catch (MineResetException $exception) {
						$sender->sendMessage(TextFormat::RED . "Error raised for {$mine->getName()}, you can reset this mine directly for more info." . TextFormat::RESET);
					}

				}
			}
			$count = count($this->getApi()->getMineManager());
			$sender->sendMessage("Queued reset for $success/$count mines.");
		} else {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
		}
		return true;
	}
}