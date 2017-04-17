<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;

class ResetAllCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        $success = 0;
        foreach ($this->getApi()->getMineManager() as $mine){
            if($mine instanceof Mine) {
                if($mine->reset()){
                    $success++;
                    $this->getApi()->getResetProgressManager()->addObserver($mine->getName(), $sender);
                }
            }
        }
        $count = count($this->getApi()->getMineManager());
        $sender->sendMessage("Queued reset for {$success}/{$count} mines.");
    }
}