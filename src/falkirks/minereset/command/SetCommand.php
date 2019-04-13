<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use falkirks\minereset\util\BlockStringParser;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SetCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(!$sender->hasPermission("minereset.command.set"))
            return $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        if (!isset($args[0]))
            return $sender->sendMessage("Usage: /mine set <name> <data>");

        $name = $args[0];
        if (!isset($this->getApi()->getMineManager()[$args[0]]))
            return $sender->sendMessage("{$args[0]} is not a valid mine.");

        if(!isset($args[2]))
            return $sender->sendMessage("You must provide at least one block with a chance value.");


        $sets = array_slice($args, 1);
        $save = [];

        //FIXME Allows bad ordering by treating every input as block string
        if(!array_reduce($sets, function ($carry, $curr){ return $carry && BlockStringParser::isValid($curr); }, true))
            return $sender->sendMessage(TextFormat::RED . "Part of your format is not a number." . TextFormat::RESET);
        if(count($sets) % 2 !== 0)
            return $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);


        $total = 0;
        foreach ($sets as $key => $item) {
            if (strpos($item, "%")) {
                return $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
            }
            if ($key & 1) {
                $total += $item;
                if (isset($save[$sets[$key - 1]])) {
                    $save[$sets[$key - 1]] += $item;
                } else {
                    $save[$sets[$key - 1]] = $item;
                }
            }
        }

        if($total !== 100)
            return $sender->sendMessage(TextFormat::RED . "The percents on your mine must add to 100, but they add to $total." . TextFormat::RESET);

        $this->getApi()->getMineManager()[$name]->setData($save);
        $sender->sendMessage(TextFormat::GREEN . "Mine has been setted. Use /mine reset $name to see your changes.");
        return true;
    }
}