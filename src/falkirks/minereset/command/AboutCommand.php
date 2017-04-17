<?php

namespace falkirks\minereset\command;


use falkirks\minereset\task\AboutPullTask;
use pocketmine\command\CommandSender;

class AboutCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        $this->getApi()->getServer()->getScheduler()->scheduleAsyncTask(new AboutPullTask($sender));
    }
}