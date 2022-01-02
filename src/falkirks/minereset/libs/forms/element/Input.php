<?php

declare(strict_types=1);

namespace falkirks\minereset\libs\forms\element;

use pocketmine\form\FormValidationException;
use function gettype;
use function is_string;

/** @phpstan-extends BaseElementWithValue<string> */
class Input extends BaseElementWithValue
{

	public function __construct(
		string $text,
		public /*readonly*/ string $placeholder,
		public /*readonly*/ string $default = "",
	) {
		parent::__construct($text);
	}

	protected function getType(): string {
		return "input";
	}

	protected function validateValue(mixed $value): void {
		if (!is_string($value)) {
			throw new FormValidationException("Expected string, got " . gettype($value));
		}
	}

	protected function serializeElementData(): array {
		return [
			"placeholder" => $this->placeholder,
			"default" => $this->default,
		];
	}
}
