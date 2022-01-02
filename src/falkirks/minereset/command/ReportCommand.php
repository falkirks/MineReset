<?php

namespace falkirks\minereset\command;


use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class ReportCommand extends SubCommand
{
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if ($sender->hasPermission("minereset.command.report")) {
			$data = $this->getApi()->getDebugDumpFactory()->generate();
			if ($sender instanceof ConsoleCommandSender) {
				$issueContent = "\n\n(Explain your problem here)\n\n```\n$data\n```";
				$url = "https://github.com/Falkirks/MineReset/issues/new" . (count($args) > 0 ? "?title=" . urlencode(implode(" ", $args)) . "\&" : "?") . "body=" . urlencode($issueContent);
				switch (Utils::getOS()) {
					case 'win':
						`start $url`;
						break;
					case 'mac':
						`open $url`;
						break;
					case 'linux':
						`xdg-open $url`;
						break;
					default:
						$sender->sendMessage("Copy and paste the following URL into your browser to start a report.");
						$sender->sendMessage("------------------");
						$sender->sendMessage($url);
						$sender->sendMessage("------------------");
						break;
				}
			}
			$sender->sendMessage("--- MineReset Data ---");
			$sender->sendMessage($data);
		} else {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
		}
		return true;
	}
}