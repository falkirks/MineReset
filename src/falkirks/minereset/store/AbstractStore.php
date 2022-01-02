<?php

namespace falkirks\minereset\store;

/**
 * This is extracted from SimpleWarp and redistributed under the same license
 * as Mine Reset.
 *
 * Interface AbstractStore
 * @package falkirks\minereset\store
 */
abstract class AbstractStore implements DataStore
{
	public function addAll(array $mines): void {
		foreach ($mines as $name => $mine) {
			$this->add($name, $mine);
		}
	}

	public function removeAll(array $mines): void {
		foreach ($mines as $mine) {
			$this->remove($mine);
		}
	}

	public function exists(string $name): bool {
		return $this->get($name) !== null;
	}
}