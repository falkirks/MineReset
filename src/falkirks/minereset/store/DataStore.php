<?php

namespace falkirks\minereset\store;

use falkirks\minereset\Mine;

/**
 * This interface deals with the storage of arbitrary mine
 * data in a key-value store.
 *
 * This is extracted from SimpleWarp and redistributed under the same license
 * as Mine Reset.
 *
 * Interface DataStore
 * @package falkirks\minereset\store
 */
interface DataStore
{
	/**
	 * @param Mine[] $mines
	 * @return void
	 */
	public function addAll(array $mines): void;

	/**
	 * @param Mine[] $mines
	 * @return void
	 */
	public function removeAll(array $mines): void;

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists(string $name): bool;

	/**
	 * This method takes a $name string and a $warp array and
	 * returns the previous value that occupied $name or null.
	 * @param string $name
	 * @param Mine $mine
	 * @return mixed
	 */
	public function add(string $name, Mine $mine): mixed;

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get(string $name): mixed;

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function remove(string $name): mixed;

	/**
	 * @return void
	 */
	public function clear(): void;

	/**
	 * Returns something which can be used to iterate
	 * over the store.
	 * @return mixed
	 */
	public function getIterator(): mixed;
}