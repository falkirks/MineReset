<?php

namespace falkirks\minereset\command;

use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ListCommand extends SubCommand
{
    public function execute(CommandSender $sender, $commandLabel, array $args): void
    {
        if ($sender->hasPermission("minereset.command.list")) {
            $sender->sendMessage("---- Mines ----");
            foreach ($this->getApi()->getMineManager() as $mine) {
                if ($mine instanceof Mine) {
                    if (!$mine->isValid()) {
                        $sender->sendMessage("* " . TextFormat::RED . $mine . TextFormat::RESET);
                    } else if ($mine->isResetting()) {
                        $sender->sendMessage("* " . TextFormat::BLUE . $mine . TextFormat::RESET);
                    } else {
                        $sender->sendMessage("* " . $mine);
                    }
                }
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }
    }
}