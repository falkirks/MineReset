<?php

namespace falkirks\minereset\command;


use falkirks\minereset\exception\InvalidBlockStringException;
use falkirks\minereset\exception\InvalidStateException;
use falkirks\minereset\exception\WorldNotFoundException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ResetCommand extends SubCommand
{
    public function execute(CommandSender $sender, $commandLabel, array $args): void
    {
        if (!$sender->hasPermission("minereset.command.reset")) {
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage("Usage: /mine reset <name>");
            return;
        }

        $mine = $this->getApi()->getMineManager()[$args[0]]; // fetch mine from the manager

        if ($mine === null) {
            $sender->sendMessage("$args[0] is not a valid mine.");
            return;
        }

        try {
            $mine->reset();
            $sender->sendMessage("Queued reset for $args[0].");
            $this->getApi()->getResetProgressManager()->addObserver($args[0], $sender);
        } catch (InvalidStateException) {
            $sender->sendMessage(TextFormat::RED . "Failed to queue reset due to bad state." . TextFormat::RESET);

            $sender->sendMessage("  --> this means the mine is already resetting");
            $sender->sendMessage("  --> wait a minute and try again ");
            $sender->sendMessage("  --> then try restarting the server ");

            $sender->sendMessage("You can run /mine report to report bugs on github.");
        } catch (WorldNotFoundException) {
            $sender->sendMessage(TextFormat::RED . "Failed to queue reset due to level not found." . TextFormat::RESET);

            $sender->sendMessage("  --> this means that the level called [{$mine->getLevelName()}] is not loaded");
            $sender->sendMessage("  --> perhaps you have changed the level name? or forgotten to load it? ");

            $sender->sendMessage("You can run /mine report to report bugs on github.");
        } catch (InvalidBlockStringException) {
            $sender->sendMessage(TextFormat::RED . "Failed to queue reset due to invalid ratio date." . TextFormat::RESET);

            $sender->sendMessage("  --> this means the saved data for the mine is invalid");
            $sender->sendMessage("  --> you should look at the mines save file and make sure it looks right ");
            $sender->sendMessage("  --> all blocks need to either be numeric ids or the exact block name ");

            $sender->sendMessage("You can run /mine report to report bugs on github.");
        }
    }
}