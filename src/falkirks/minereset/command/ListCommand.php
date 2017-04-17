<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;

class ListCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        foreach ($this->getApi()->getMineManager() as $mine){
            if($mine instanceof Mine) {
                $sender->sendMessage($mine->getName());
            }
        }
    }
}