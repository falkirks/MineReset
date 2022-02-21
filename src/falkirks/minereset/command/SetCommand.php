<?php

namespace falkirks\minereset\command;


use falkirks\minereset\util\BlockStringParser;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SetCommand extends SubCommand
{
    public function execute(CommandSender $sender, $commandLabel, array $args): void
    {
        if (!$sender->hasPermission("minereset.command.set")) {
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
            return;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("Usage: /mine set <name> < data>");
            return;
        }

        $name = $args[0];
        if (!isset($this->getApi()->getMineManager()[$args[0]])) {
            $sender->sendMessage("$args[0] is not a valid mine.");
            return;
        }

        if (!isset($args[2])) {
            $sender->sendMessage("You must provide at least one block with a chance value.");
            return;
        }


        $sets = array_slice($args, 1);
        $save = [];

        //FIXME Allows bad ordering by treating every input as block string
        if (!array_reduce($sets, static function ($carry, $curr) { return $carry && BlockStringParser::isValid($curr); }, true)) {
            $sender->sendMessage(TextFormat::RED . "Part of your format is not a number." . TextFormat::RESET);
            return;
        }
        if (count($sets) % 2 !== 0) {
            $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
            return;
        }


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
                } else {
                    $save[$sets[$key - 1]] = $item;
                }
            }
        }

        if ($total !== 100) {
            $sender->sendMessage(TextFormat::RED . "The percents on your mine must add to 100, but they add to $total." . TextFormat::RESET);
            return;
        }

        $this->getApi()->getMineManager()[$name]->setData($save);
        $sender->sendMessage(TextFormat::GREEN . "Mine has been setted. Use /mine reset $name to see your changes.");
    }
}