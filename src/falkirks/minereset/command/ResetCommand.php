<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;

class ResetCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(isset($args[0])){
            if(isset($this->getApi()->getMineManager()[$args[0]])){
                if($this->getApi()->getMineManager()[$args[0]]->reset()){
                    $sender->sendMessage("Queued reset for {$args[0]}.");
                    $this->getApi()->getResetProgressManager()->addObserver($args[0], $sender);
                }
                else{
                    $sender->sendMessage("Could not queue reset for {$args[0]}.");
                }
            }
            else{
                $sender->sendMessage("{$args[0]} is not a valid mine.");
            }
        }
        else{
            $sender->sendMessage("Usage: /mine reset <name>");
        }
    }
}