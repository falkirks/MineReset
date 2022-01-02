<?php

namespace falkirks\minereset\store;


use falkirks\minereset\MineReset;
use pocketmine\entity\Entity;

class EntityStore
{
	/** @var array */
	private array $store;
	/** @var  MineReset */
	private MineReset $api;

	/**
	 * EntityStore constructor.
	 * @param MineReset $api
	 */
	public function __construct(MineReset $api) {
		$this->api = $api;
		$this->store = [];
	}

	/**
	 * @param string $mineName
	 * @param Entity[] $entities
	 * @return void
	 */
	public function storeEntities(string $mineName, array $entities): void {
		$this->store[$mineName] = $entities;
	}

	/**
	 * @param string $mineName
	 * @return Entity[]
	 */
	public function retrieveEntities(string $mineName): array {
		if (isset($this->store[$mineName])) {
			$entities = $this->store[$mineName];
			unset($this->store[$mineName]);
			return $entities;
		}
		return [];
	}

	/**
	 * @return MineReset
	 */
	public function getApi(): MineReset {
		return $this->api;
	}

}