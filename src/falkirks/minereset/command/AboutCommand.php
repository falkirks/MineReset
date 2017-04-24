<?php

namespace falkirks\minereset\command;


use falkirks\minereset\task\AboutPullTask;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AboutCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if($sender->hasPermission("minereset.command.about")) {
            $this->getApi()->getServer()->getScheduler()->scheduleAsyncTask(new AboutPullTask($sender));
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }
    }
}