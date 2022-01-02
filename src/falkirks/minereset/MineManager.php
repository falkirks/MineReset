<?php

namespace falkirks\minereset;


use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use falkirks\minereset\exception\JsonFieldMissingException;
use falkirks\minereset\store\ConfigStore;
use falkirks\minereset\store\DataStore;
use falkirks\minereset\store\Reloadable;
use falkirks\minereset\store\Saveable;
use IteratorAggregate;
use JsonException;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;
use Traversable;

class MineManager implements ArrayAccess, IteratorAggregate, Countable
{
	const MEMORY_TILL_CLOSE = 0;
	const FLUSH_ON_CHANGE = 1;

	/** @var MineReset */
	private MineReset $api;
	/** @var null|DataStore */
	private ?DataStore $store;
	/** @var  Mine[] */
	private array $mines;
	/** @var mixed|int */
	private mixed $flag;

	public function __construct(MineReset $api, DataStore $store, int $flag = MineManager::FLUSH_ON_CHANGE) {
		$this->api = $api;
		$this->store = $store;
		$this->flag = $flag;
		$this->mines = [];
		if (file_exists($api->getDataFolder() . "mines.yml")) {
			rename($api->getDataFolder() . "mines.yml", $api->getDataFolder() . "_minesOLD.yml");
			$api->getLogger()->info("MineReset is upgrading your old mines.yml to the newest config version");
			$api->getLogger()->info("A backup of your old file will be placed at " . TextFormat::BLACK . "_minesOLD.yml" . TextFormat::RESET);
			$this->triggerConfigUpdate();

		}
		if ($this->flag < 2) {
			$this->mines = $this->loadMines();
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function triggerConfigUpdate(): void {
		$store = new ConfigStore(new Config($this->getApi()->getDataFolder() . "_minesOLD.yml", Config::YAML));
		foreach ($store->getIterator() as $name => $data) {
			$this->mines[$name] = $this->mineFromData($name, $data);
		}
		foreach ($this->mines as $mine) {
			$this->store->add($mine->getName(), $mine);
		}
		$this->saveStore(true);
	}

	/**
	 * @deprecated
	 */
	protected function reloadStore(): void {
		if ($this->flag >= 2 && $this->store instanceof Reloadable) {
			$this->store->reload();
		}
	}

	/**
	 * @param bool $force
	 * @return void
	 * @throws JsonException
	 */
	protected function saveStore(bool $force = false): void {
		if (($this->flag > 0 || $force) && $this->store instanceof Saveable) {
			$this->store->save();
		}
	}

	protected function loadMines(): array {
		$out = [];
		foreach ($this->store->getIterator() as $name => $data) {
			try {
				$out[$name] = Mine::fromJson($this, $data, $name);
			} catch (JsonFieldMissingException $e) {
				$this->getApi()->getLogger()->warning("Mine with name " . $name . " is missing data in save file. Will be deleted on next save.");
			}
			//$out[$name] = $this->mineFromData($name, $data);
		}
		return $out;
	}

	/**
	 * WARNING
	 * This function is for internal use only.
	 * @throws JsonException
	 */
	public function saveAll(): void {
		if ($this->flag === 0) {
			$this->store->clear();
			foreach ($this->mines as $mine) {
				$this->store->add($mine->getName(), $mine);
			}
			$this->saveStore(true);
		}
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists(mixed $offset): bool {
		return isset($this->mines[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->mines[$offset] ?? null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @throws JsonException
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($value instanceof Mine && $value->getName() === $offset) {

			if (isset($this->mines[$offset]) && $value !== $this->mines[$offset] && $this->mines[$offset] instanceof Mine) {
				$this->mines[$offset]->destroy();
			}

			$this->mines[$offset] = $value;
			if ($this->flag === 1) {
				$this->store->add($offset, $value);
				$this->saveStore();
			}
		} else {
			throw new RuntimeException("Invalid $offset for mine data.");
		}
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @throws JsonException
	 */
	public function offsetUnset(mixed $offset): void {
		if (isset($this->mines[$offset])) {
			if ($this->mines[$offset] instanceof Mine) {
				$this->mines[$offset]->destroy();
			}
			unset($this->mines[$offset]);
			if ($this->flag === 1) {
				$this->store->remove($offset);
				$this->saveStore();
			}
		}
	}

	/**
	 * This method requires the key of the warp in order
	 * to construct a mine object
	 * @param string $name
	 * @param array $array
	 * @return Mine|null
	 * @DEPRECATED
	 */
	protected function mineFromData(string $name, array $array): ?Mine {
		if (count($array) === 9 || count($array) === 8) {
			if (!$this->getApi()->getServer()->getWorldManager()->isWorldLoaded($array[7])) {
				$this->api->getLogger()->warning("A mine with the name " . TextFormat::AQUA . $name . TextFormat::RESET . " is connected to a level which is not loaded. You won't be able to use it until you load the level correctly.");
			}
			return new Mine($this,
				new Vector3(min($array[0], $array[1]), min($array[2], $array[3]), min($array[4], $array[5])),
				new Vector3(max($array[0], $array[1]), max($array[2], $array[3]), max($array[4], $array[5])),
				$array[7],
				$name,
				(is_array($array[6]) ? $array[6] : []),
				$array[8] ?? -1);
		}
		$this->api->getLogger()->critical("A mine with the name " . TextFormat::AQUA . $name . TextFormat::RESET . " is incomplete. It will be removed automatically when your server stops.");
		return null;
	}

	/**
	 * In order to pass data to a DataStore
	 * a key is needed. Typically one should
	 * use $warp->getName()
	 * @param Mine $mine
	 * @return array
	 * @DEPRECATED
	 */
	protected function mineToData(Mine $mine): array {
		return [
			$mine->getPointA()->getX(),
			$mine->getPointB()->getX(),
			$mine->getPointA()->getY(),
			$mine->getPointB()->getY(),
			$mine->getPointA()->getZ(),
			$mine->getPointB()->getZ(),
			(count($mine->getData()) > 0 ? $mine->getData() : false),
			$mine->getWorldName(),
			$mine->getResetInterval()
		];
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable|ArrayIterator An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 */
	public function getIterator(): Traversable|ArrayIterator {
		return new ArrayIterator($this->mines);
	}

	public function count(): int {
		return count($this->mines);
	}


	/**
	 * Returns the current storage-mode
	 * #####
	 *  MEMORY_TILL_CLOSE = 0
	 * Mines are loaded into memory when the server starts and are
	 * held there until the server closes. When the server closes
	 * they are converted back into YAML. This new YAML will
	 * replace mines.yml, this means that changes are lost and
	 * warps which fail to load are discarded.
	 *
	 *
	 * FLUSH_ON_CHANGE = 1
	 * Mines are loaded into memory when the server starts. Whenever a
	 * mine is updated, it will be updated in the mines.yml. When the
	 * server closes, the mines file is NOT overwritten.
	 *
	 * NO_MEMORY_STORE = 2
	 * THIS IS NOT SUPPORTED
	 * ####
	 * @return int
	 */
	public function getFlag(): int {
		return $this->flag;
	}

	/**
	 * returns the current data store
	 * @return DataStore
	 */
	public function getStore(): DataStore {
		return $this->store;
	}

	/**
	 * Injects a new DataStore for warps
	 * ! This will inject your code into MineReset, potentially breaking!
	 * @param DataStore $store
	 * @throws JsonException
	 */
	public function setStore(DataStore $store): void {
		$this->saveAll();
		$this->store = $store;
		$this->mines = $this->loadMines();
	}

	/**
	 * @return MineReset
	 */
	public function getApi(): MineReset {
		return $this->api;
	}

	/**
	 * @return Mine[]
	 */
	public function getMines(): array {
		return $this->mines;
	}
}