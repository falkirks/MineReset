<?php

namespace falkirks\minereset\command;


use falkirks\minereset\libs\forms\ModalForm;
use falkirks\minereset\MineReset;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class DestroyCommand extends SubCommand
{

	const DESTROY_STRINGS = [
		"a",
		"b",
		"c",
		"5",
		"7",
		"-f",
		"DEATH",
		"yes",
		"15",
		"y"
	];

	private int $offset;
	private array $senders;

	public function __construct(MineReset $mineReset) {
		parent::__construct($mineReset);
		$this->offset = 0;
		$this->senders = [];
	}

	public function doDelete(CommandSender $sender, string $name): void {
		unset($this->getApi()->getMineManager()[$name]);
		unset($this->senders[$sender->getName()]);
		$sender->sendMessage("$name[0] has been destroyed.");
	}

	private function formDelete(Player $sender, string $name): void {
		$form = new ModalForm("Are you sure?", "You are about to delete the mine called $name.",
			function (Player $player, bool $response) use ($name): void {
				if ($response) {
					$this->doDelete($player, $name);
				}
			});
		$sender->sendForm($form);
	}

	private function basicDelete(CommandSender $sender, string $name): void {
		$str = DestroyCommand::DESTROY_STRINGS[$this->offset];
		$sender->sendMessage("Run: " . TextFormat::AQUA . "/mine destroy $name $str" . TextFormat::RESET);
		$sender->sendMessage("To destroy mines faster, you can edit the config file directly.");
		$this->senders[$sender->getName()] = $str;

		if ($this->offset === count(DestroyCommand::DESTROY_STRINGS) - 1) {
			$this->offset = -1;
		}

		$this->offset++;
	}


	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if (!$sender->hasPermission("minereset.command.destroy")) {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
			return false;
		}

		if (!isset($args[0])) {
			$sender->sendMessage("Usage: /mine destroy <name>");
			return false;
		}

		$name = $args[0];

		if (!isset($this->getApi()->getMineManager()[$name])) {
			$sender->sendMessage("$args[0] is not a valid mine.");
			return false;
		}

		if ($sender instanceof Player) {
			$this->formDelete($sender, $name);
		} else if (isset($args[1]) && isset($this->senders[$sender->getName()]) && $this->senders[$sender->getName()] === $args[1]) {
			$this->doDelete($sender, $name);
		} else {
			$this->basicDelete($sender, $name);
		}

		return true;
	}
}