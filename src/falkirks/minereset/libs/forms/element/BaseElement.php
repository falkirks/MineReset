<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\element;

use pocketmine\form\FormValidationException;

abstract class BaseElement implements \JsonSerializable
{

	public function __construct(public /*readonly*/ string $text) {
	}

	abstract protected function getType(): string;

	/**
	 * @throws FormValidationException
	 */
	abstract protected function validateValue(mixed $value): void;

	/** @phpstan-return array<string, mixed> */
	abstract protected function serializeElementData(): array;

	/** @phpstan-return array<string, mixed> */
	final public function jsonSerialize(): array {
		$ret = $this->serializeElementData();
		$ret["type"] = $this->getType();
		$ret["text"] = $this->text;

		return $ret;
	}
}
