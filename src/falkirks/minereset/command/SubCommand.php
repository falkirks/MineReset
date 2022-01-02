<?php

namespace falkirks\minereset\command;


use falkirks\minereset\MineReset;
use pocketmine\command\CommandSender;

abstract class SubCommand
{
	/** @var MineReset */
	private MineReset $api;

	/**
	 * SubCommand constructor.
	 * @param MineReset $api
	 */
	public function __construct(MineReset $api) {
		$this->api = $api;
	}


	abstract public function execute(CommandSender $sender, string $commandLabel, array $args): bool;

	/**
	 * @return MineReset
	 */
	public function getApi(): MineReset {
		return $this->api;
	}
}