<?php

namespace falkirks\minereset\command;


use falkirks\minereset\libs\forms\CustomForm;
use falkirks\minereset\libs\forms\CustomFormResponse;
use falkirks\minereset\libs\forms\element\Dropdown;
use falkirks\minereset\libs\forms\element\Input;
use falkirks\minereset\libs\forms\element\Label;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class EditCommand extends SubCommand
{
	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if ($sender->hasPermission("minereset.command.edit")) {
			if ($sender instanceof Player) {
				$form = new CustomForm("Mine: a", [
					new Dropdown("Select product", ["beer", "cheese", "cola"]),
					new Input("Mine name", "a"),
					new Input("Reset interval", "-1"),
					new Label("Reset interval is in seconds"), //popElement() does not work with label
					new Input("Warp name", ""),
					new Label("Name of the warp to link with the mine"),
				], function (Player $player, CustomFormResponse $response): void {
					$player->sendMessage("cool!");
				});
				$sender->sendForm($form);
			}
		} else {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
		}
		return true;
	}
}