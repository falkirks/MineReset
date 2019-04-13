<?php
namespace falkirks\minereset\command;


use falkirks\minereset\listener\MineCreationSession;
use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CreateCommand extends SubCommand{


    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(!$sender->hasPermission("minereset.command.create"))
            return $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);

        if (!($sender instanceof Player))
            return $sender->sendMessage(TextFormat::RED . "This command can only be run in-game." . TextFormat::RESET);

        if(!isset($args[0]))
            return $sender->sendMessage("Usage: /mine create <name>");

        if($this->getApi()->getCreationListener()->playerHasSession($sender))
            return $sender->sendMessage("Hold up! You are already in the process of creating a mine. You need to finish that first.");

        if(isset($this->getApi()->getMineManager()[$args[0]]))
            return $sender->sendMessage("That mine already exists. You must run \"/mine destroy {$args[0]}\" before creating a new one.");

        $this->getApi()->getCreationListener()->addSession(new MineCreationSession($args[0], $sender));
        $sender->sendMessage("Tap a block to set position A.");
        return true;
    }
}