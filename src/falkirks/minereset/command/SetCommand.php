<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SetCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if($sender->hasPermission("minereset.command.set")) {
            if (isset($args[0])) {
                if (isset($this->getApi()->getMineManager()[$args[0]])) {
                    if (isset($args[2])) {
                        $sets = array_slice($args, 1);
                        $save = [];
                        if (count($sets) % 2 === 0) {
                            $total = 0;
                            foreach ($sets as $key => $item) {
                                if (strpos($item, "%")) {
                                    $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
                                    return;
                                }
                                if ($key & 1) {
                                    $total += $item;
                                    if (isset($save[$sets[$key - 1]])) {
                                        $save[$sets[$key - 1]] += $item;
                                    }
                                    else {
                                        $save[$sets[$key - 1]] = $item;
                                    }
                                }
                            }
                            if($total === 100) {
                                $this->getApi()->getMineManager()[$args[0]]->setData($save);
                                $sender->sendMessage(TextFormat::GREEN . "Mine has been setted. Use /mine reset {$args[0]} to see your changes.");
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "The percents on your mine must add to 100, but they add to {$total}." . TextFormat::RESET);
                            }
                        }
                        else {
                            $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
                        }
                    }
                    else {
                        $sender->sendMessage("You must provide at least one block with a chance value.");
                    }
                }
                else {
                    $sender->sendMessage("{$args[0]} is not a valid mine.");
                }
            }
            else {
                $sender->sendMessage("Usage: /mine set <name> <data>");
            }
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }
    }
}