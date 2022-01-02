<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\menu;

class Button implements \JsonSerializable
{

	/** @var array */
	private array $entries = [];

	public function __construct(public /*readonly*/ string $text, public /*readonly*/ ?Image $image = null, private ?int $value = null) {
	}

	public function getValue(): int {
		return $this->value ?? throw new \InvalidStateException("Trying to access an uninitialized value");
	}

	public function setValue(int $value): self {
		$this->value = $value;
		return $this;
	}

	public function getEntry(mixed $index): mixed {
		return $this->entries[$index] ?? null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function addEntry(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->entries[] = $value;
		} else {
			$this->entries[$offset] = $value;
		}
	}

	/** @phpstan-return array<string, mixed> */
	public function jsonSerialize(): array {
		$ret = ["text" => $this->text];
		if ($this->image !== null) {
			$ret["image"] = $this->image;
		}

		return $ret;
	}
}
