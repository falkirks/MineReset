<?php

namespace falkirks\minereset\store;

use JsonException;
use pocketmine\utils\Config;

/**
 * Class ConfigStore
 * @package falkirks\minereset\store
 */
class ConfigStore extends AbstractStore implements Saveable, Reloadable
{
	/** @var Config */
	private Config $config;

	/**
	 * YAMLStore constructor.
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
	}

	/**
	 * Adds a new mine and returns the old one
	 * @param $name
	 * @param $mine
	 * @return bool|mixed
	 * @throws JsonException
	 */
	public function add($name, $mine): mixed {
		$past = $this->config->get($name, null);
		$this->config->set($name, $mine);
		$this->config->save();
		return $past;
	}

	/**
	 * Gets mine with $name
	 * @param $name
	 * @return bool|mixed
	 */
	public function get($name): mixed {
		return $this->config->get($name, null);
	}

	/**
	 * Removes a mine with $name and returns it
	 * @param $name
	 * @return bool|mixed
	 * @throws JsonException
	 */
	public function remove($name): mixed {
		$past = $this->config->get($name, null);
		$this->config->remove($name);
		$this->config->save();
		return $past;
	}

	/**
	 * Clears all mines
	 * @throws JsonException
	 */
	public function clear(): void {
		$this->config->setAll([]);
		$this->config->save();
	}

	/**
	 *  Reloads the mines from YAML
	 */
	public function reload(): void {
		$this->config->reload();
	}

	/**
	 * Returns something which can be used to iterate
	 * over the store.
	 * @return array
	 */
	public function getIterator(): array {
		return $this->config->getAll();
	}

	/**
	 * Saves mines to file
	 * @throws JsonException
	 */
	public function save(): void {
		$this->config->save();
	}
}